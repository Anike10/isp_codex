<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\OltOnu;
use App\Models\PppLiveSession;
use App\Models\PppUsageLog;
use App\Support\Mac;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns successive `/ppp/active` reads into durable per-session usage rows.
 *
 * RouterOS does not consistently expose byte/uptime event variables to PPP
 * profile `on-down` scripts. While a session is live, however, `/ppp/active`
 * exposes those counters reliably. We retain its last observed values and
 * finalise a usage row once the session disappears from a successful poll.
 */
class PppSessionSnapshotService
{
    /**
     * @param  Collection<int, array<string, mixed>>  $sessions
     * @param  Collection<int, array<string, mixed>>  $interfaces
     * @return array{captured: int, finalised: int}
     */
    public function sync(MikrotikRouter $router, Collection $sessions, Collection $interfaces): array
    {
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

        return DB::transaction(function () use ($router, $sessions, $customersByName, $interfacesByName, $now): array {
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
                    $this->finalise($existing);
                    $existing->delete();
                    $stored->forget($sessionId);
                    $existing = null;
                    $finalised++;
                }

                // On these RouterOS builds `/ppp/active` supplies identity and
                // uptime, while the live dynamic `<pppoe-user>` interface owns
                // the actual counters. Prefer PPP fields if a build has them.
                $download = $this->counter($row['bytes-out'] ?? $interface['tx-byte'] ?? null);
                $upload = $this->counter($row['bytes-in'] ?? $interface['rx-byte'] ?? null);
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
            // a once-per-minute poll cheap enough for the production DB.
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

            foreach ($missing as $snapshot) {
                $this->finalise($snapshot);
                $snapshot->delete();
                $finalised++;
            }

            return ['captured' => count($seen), 'finalised' => $finalised];
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
            ->whereIn('source', ['snapshot', 'webhook+snapshot'])
            ->where('disconnected_at', '>=', now()->subMinutes(5))
            ->latest('disconnected_at')
            ->latest('id')
            ->first();
    }

    private function finalise(PppLiveSession $snapshot): PppUsageLog
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

        $onu = $this->onuForCallerId($snapshot->caller_id);
        $attributes = [
            'mikrotik_router_id' => $snapshot->mikrotik_router_id,
            'customer_id' => $snapshot->customer_id ?? $log?->customer_id,
            'olt_onu_id' => $onu?->id ?? $log?->olt_onu_id,
            'username' => $snapshot->username,
            'caller_id' => $snapshot->caller_id ?? $log?->caller_id,
            'source' => $log ? 'webhook+snapshot' : 'snapshot',
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

        return OltOnu::query()
            ->where(function ($query) use ($mac): void {
                $query->whereRaw('lower(mac_address) = ?', [$mac])
                    ->orWhere('learned_macs', 'like', '%"'.$mac.'"%');
            })
            ->orderByDesc('last_live_polled_at')
            ->first();
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
