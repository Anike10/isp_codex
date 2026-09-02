<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\OltOnu;
use App\Models\PppLiveSession;
use App\Models\PppUsageLog;
use App\Support\Mac;
use App\Support\OnuMatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns `/ppp/active` snapshots and listen events into durable usage rows.
 *
 * RouterOS does not consistently expose byte/uptime event variables to PPP
 * profile `on-down` scripts. While a session is live, however, `/ppp/active`
 * exposes those counters reliably. We retain its last observed values and
 * finalise a usage row when RouterOS emits the session's `.dead=yes` event.
 * A full snapshot is still accepted for listener startup reconciliation and
 * the manual sync command, but normal collection does not require polling.
 */
class PppSessionSnapshotService
{
    /**
     * @param  Collection<int, array<string, mixed>>  $sessions
     * @param  Collection<int, array<string, mixed>>  $interfaces
     * @return array{captured: int, finalised: int}
     */
    public function sync(
        MikrotikRouter $router,
        Collection $sessions,
        Collection $interfaces,
        string $source = 'snapshot',
        bool $finaliseMissing = true
    ): array {
        $sessions = $sessions
            ->filter(fn ($row): bool => is_array($row)
                && filled($row['.id'] ?? null)
                && filled($row['name'] ?? null))
            ->values();

        $customersByName = $this->customersByName($router, $sessions->pluck('name'));
        $interfacesByName = $interfaces
            ->filter(fn ($row): bool => is_array($row) && filled($row['name'] ?? null))
            ->keyBy(fn ($row): string => mb_strtolower(trim((string) $row['name'])));
        $now = now();

        return DB::transaction(function () use ($router, $sessions, $customersByName, $interfacesByName, $now, $source, $finaliseMissing): array {
            $stored = PppLiveSession::query()
                ->where('mikrotik_router_id', $router->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('routeros_session_id');

            $seen = [];
            $upserts = [];
            $finalised = 0;

            foreach ($sessions as $row) {
                $sessionId = trim((string) $row['.id']);
                $username = trim((string) $row['name']);
                $key = mb_strtolower($username);
                $existing = $stored->get($sessionId);
                $service = trim((string) ($row['service'] ?? 'pppoe')) ?: 'pppoe';
                $interfaceName = '<'.$service.'-'.$username.'>';
                $interface = $interfacesByName->get(mb_strtolower($interfaceName), []);

                // RouterOS can eventually reuse a short `*AB` id. If it now
                // belongs to another username, close the old snapshot first.
                if ($existing && mb_strtolower($existing->username) !== $key) {
                    $this->finalise($existing, $source);
                    $existing->delete();
                    $stored->forget($sessionId);
                    $existing = null;
                    $finalised++;
                }

                // On these RouterOS builds `/ppp/active` supplies identity and
                // uptime, while the live dynamic `<pppoe-user>` interface owns
                // the actual counters. Prefer PPP fields if a build has them.
                [$combinedDownload, $combinedUpload] = $this->combinedCounters($row['bytes'] ?? null);
                $download = $this->counter($row['bytes-out'] ?? $interface['tx-byte'] ?? $combinedDownload);
                $upload = $this->counter($row['bytes-in'] ?? $interface['rx-byte'] ?? $combinedUpload);
                $uptime = $this->cleanString($row['uptime'] ?? null);
                $callerId = $this->cleanString($row['caller-id'] ?? null);

                $upserts[] = [
                    'mikrotik_router_id' => $router->id,
                    'routeros_session_id' => $sessionId,
                    'customer_id' => $customersByName[$key]?->id ?? $existing?->customer_id,
                    'username' => $username,
                    'caller_id' => $callerId ?? $existing?->caller_id,
                    'uptime' => $uptime ?? $existing?->uptime,
                    'uptime_seconds' => $uptime !== null
                        ? $this->uptimeToSeconds($uptime)
                        : $existing?->uptime_seconds,
                    // Counters are monotonic during one RouterOS session. An
                    // empty/partial response must never erase a good reading.
                    'download_bytes' => $download === null
                        ? (int) ($existing?->download_bytes ?? 0)
                        : max($download, (int) ($existing?->download_bytes ?? 0)),
                    'upload_bytes' => $upload === null
                        ? (int) ($existing?->upload_bytes ?? 0)
                        : max($upload, (int) ($existing?->upload_bytes ?? 0)),
                    'payload' => json_encode(['active' => $row, 'interface' => $interface]),
                    'first_seen_at' => $existing?->first_seen_at ?? $now,
                    'last_seen_at' => $now,
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ];

                $seen[] = $sessionId;
            }

            // Hundreds of live sessions are normal. A single bulk upsert keeps
            // startup/manual reconciliation cheap for the production DB.
            if ($upserts !== []) {
                PppLiveSession::query()->upsert(
                    $upserts,
                    ['mikrotik_router_id', 'routeros_session_id'],
                    [
                        'customer_id', 'username', 'caller_id', 'uptime',
                        'uptime_seconds', 'download_bytes', 'upload_bytes',
                        'payload', 'last_seen_at', 'updated_at',
                    ]
                );
            }

            $missing = $stored->reject(fn (PppLiveSession $snapshot): bool => in_array(
                $snapshot->routeros_session_id,
                $seen,
                true
            ));

            if ($finaliseMissing) {
                foreach ($missing as $snapshot) {
                    $this->finalise($snapshot, $source);
                    $snapshot->delete();
                    $finalised++;
                }
            }

            return ['captured' => count($seen), 'finalised' => $finalised];
        });
    }

    /**
     * Apply one record emitted by `/ppp/active/listen`.
     *
     * The worker merges partial updates in memory before calling this method,
     * but the persisted payload is merged too so a reconnect or process restart
     * never turns a sparse `.dead=yes` record into a zero-byte usage row.
     *
     * @param  array<string, mixed>  $event
     * @return 'added'|'updated'|'finalised'|'ignored'
     */
    public function applyEvent(MikrotikRouter $router, array $event): string
    {
        $sessionId = trim((string) ($event['.id'] ?? ''));
        if ($sessionId === '') {
            return 'ignored';
        }

        return DB::transaction(function () use ($router, $event, $sessionId): string {
            $snapshot = PppLiveSession::query()
                ->where('mikrotik_router_id', $router->id)
                ->where('routeros_session_id', $sessionId)
                ->lockForUpdate()
                ->first();

            $previous = is_array($snapshot?->payload)
                ? (array) ($snapshot->payload['active'] ?? [])
                : [];
            $active = array_replace($previous, $event);
            $username = trim((string) ($active['name'] ?? $snapshot?->username ?? ''));
            $dead = filter_var($event['.dead'] ?? false, FILTER_VALIDATE_BOOL);

            if ($username === '') {
                return 'ignored';
            }

            // RouterOS may eventually reuse a short internal id. Close a stale
            // row before assigning that id to a different username.
            if ($snapshot && mb_strtolower($snapshot->username) !== mb_strtolower($username)) {
                $this->finalise($snapshot, 'listener');
                $snapshot->delete();
                $snapshot = null;
            }

            $customer = $this->customersByName($router, collect([$username]))[mb_strtolower($username)] ?? null;
            [$combinedDownload, $combinedUpload] = $this->combinedCounters($active['bytes'] ?? null);
            $download = $this->counter($active['bytes-out'] ?? $active['tx-byte'] ?? $combinedDownload);
            $upload = $this->counter($active['bytes-in'] ?? $active['rx-byte'] ?? $combinedUpload);
            $uptime = $this->cleanString($active['uptime'] ?? null);
            $callerId = $this->cleanString($active['caller-id'] ?? null);
            $now = now();

            $attributes = [
                'customer_id' => $customer?->id ?? $snapshot?->customer_id,
                'username' => $username,
                'caller_id' => $callerId ?? $snapshot?->caller_id,
                'uptime' => $uptime ?? $snapshot?->uptime,
                'uptime_seconds' => $uptime !== null
                    ? $this->uptimeToSeconds($uptime)
                    : $snapshot?->uptime_seconds,
                'download_bytes' => $download === null
                    ? (int) ($snapshot?->download_bytes ?? 0)
                    : max($download, (int) ($snapshot?->download_bytes ?? 0)),
                'upload_bytes' => $upload === null
                    ? (int) ($snapshot?->upload_bytes ?? 0)
                    : max($upload, (int) ($snapshot?->upload_bytes ?? 0)),
                'payload' => ['active' => $active],
                'last_seen_at' => $now,
            ];

            $created = $snapshot === null;
            if ($created) {
                $snapshot = PppLiveSession::create($attributes + [
                    'mikrotik_router_id' => $router->id,
                    'routeros_session_id' => $sessionId,
                    'first_seen_at' => $now,
                ]);
            } else {
                $snapshot->forceFill($attributes)->save();
            }

            if (! $dead) {
                return $created ? 'added' : 'updated';
            }

            $this->finalise($snapshot, 'listener');
            $snapshot->delete();

            return 'finalised';
        });
    }

    /**
     * Merge with an on-down row received after polling already noticed the
     * disconnect. This handles webhook retries/races without double-counting.
     */
    public function recentSnapshotLog(MikrotikRouter $router, string $username): ?PppUsageLog
    {
        return PppUsageLog::query()
            ->where('mikrotik_router_id', $router->id)
            ->whereRaw('lower(username) = ?', [mb_strtolower(trim($username))])
            ->whereIn('source', [
                'snapshot', 'listener', 'webhook+snapshot', 'webhook+listener',
            ])
            ->where('disconnected_at', '>=', now()->subMinutes(5))
            ->latest('disconnected_at')
            ->latest('id')
            ->first();
    }

    private function finalise(PppLiveSession $snapshot, string $source): PppUsageLog
    {
        $since = ($snapshot->last_seen_at ?? now())->copy()->subSeconds(10);
        $log = PppUsageLog::query()
            ->where('mikrotik_router_id', $snapshot->mikrotik_router_id)
            ->whereRaw('lower(username) = ?', [mb_strtolower($snapshot->username)])
            ->where(function ($query): void {
                $query->whereNull('source')->orWhere('source', 'webhook');
            })
            ->where('disconnected_at', '>=', $since)
            ->latest('disconnected_at')
            ->latest('id')
            ->first();

        $payload = is_array($log?->payload) ? $log->payload : [];
        $payload['ppp_active_snapshot'] = $snapshot->payload;
        $activePayload = is_array($snapshot->payload)
            ? (array) ($snapshot->payload['active'] ?? [])
            : [];
        $disconnectReason = $this->cleanString(
            $activePayload['last-disconnect-reason']
                ?? $activePayload['disconnect-reason']
                ?? null
        );

        $onu = $this->onuForCallerId($snapshot->caller_id);
        $attributes = [
            'mikrotik_router_id' => $snapshot->mikrotik_router_id,
            'customer_id' => $snapshot->customer_id ?? $log?->customer_id,
            'olt_onu_id' => $onu?->id ?? $log?->olt_onu_id,
            'username' => $snapshot->username,
            'caller_id' => $snapshot->caller_id ?? $log?->caller_id,
            'disconnect_reason' => $disconnectReason ?? $log?->disconnect_reason,
            'source' => $log ? 'webhook+'.$source : $source,
            'routeros_session_id' => $snapshot->routeros_session_id,
            'reported_router_id' => (string) $snapshot->mikrotik_router_id,
            'uptime' => $snapshot->uptime ?? $log?->uptime,
            'uptime_seconds' => $snapshot->uptime_seconds ?? $log?->uptime_seconds,
            'download_bytes' => $snapshot->download_bytes,
            'upload_bytes' => $snapshot->upload_bytes,
            'rx_power_dbm' => $onu?->rx_power_dbm ?? $log?->rx_power_dbm,
            'tx_power_dbm' => $onu?->tx_power_dbm ?? $log?->tx_power_dbm,
            'payload' => $payload,
            'disconnected_at' => $log?->disconnected_at ?? now(),
        ];

        if ($log) {
            $log->forceFill($attributes)->save();

            return $log;
        }

        return PppUsageLog::create($attributes);
    }

    /** @return array<string, Customer> */
    private function customersByName(MikrotikRouter $router, Collection $names): array
    {
        $names = $names
            ->map(fn ($name): string => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($names === []) {
            return [];
        }

        $customers = [];
        Customer::query()
            ->assignedToMikrotikRouter($router->id)
            ->where(function ($query) use ($names): void {
                $query->whereIn(DB::raw('lower(mikrotik_username)'), $names)
                    ->orWhereIn(DB::raw('lower(connection_id)'), $names);
            })
            ->get()
            ->each(function (Customer $customer) use (&$customers): void {
                foreach (array_filter([$customer->mikrotik_username, $customer->connection_id]) as $name) {
                    $customers[mb_strtolower(trim((string) $name))] = $customer;
                }
            });

        return $customers;
    }

    private function onuForCallerId(?string $callerId): ?OltOnu
    {
        $mac = Mac::colon($callerId);
        if ($mac === null) {
            return null;
        }

        return OnuMatcher::byMac([$mac])[$mac] ?? null;
    }

    private function cleanString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || mb_strtolower($value) === 'null' ? null : $value;
    }

    private function counter(mixed $value): ?int
    {
        $value = $this->cleanString($value);

        return $value !== null && is_numeric($value)
            ? max(0, (int) round((float) $value))
            : null;
    }

    /**
     * RouterOS `print stats` can expose the pair as `bytes=tx/rx` instead of
     * separate byte fields. From the router's point of view tx is download and
     * rx is upload for a connected PPP client.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function combinedCounters(mixed $value): array
    {
        $value = $this->cleanString($value);
        if ($value === null) {
            return [null, null];
        }

        $parts = preg_split('/\s*\/\s*/', $value, 2);
        if (! is_array($parts) || count($parts) !== 2) {
            return [null, null];
        }

        return [$this->counter($parts[0]), $this->counter($parts[1])];
    }

    public function uptimeToSeconds(string $uptime): int
    {
        $uptime = trim(mb_strtolower($uptime));
        if ($uptime === '') {
            return 0;
        }

        $seconds = 0;
        $units = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];

        if (preg_match_all('/(\d+)\s*([wdhms])/', $uptime, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $seconds += (int) $match[1] * $units[$match[2]];
            }

            return $seconds;
        }

        if (preg_match('/(?:(\d+)d)?(\d{1,2}):(\d{2}):(\d{2})/', $uptime, $clock)) {
            return (int) ($clock[1] ?: 0) * 86400
                + (int) $clock[2] * 3600
                + (int) $clock[3] * 60
                + (int) $clock[4];
        }

        return 0;
    }
}
