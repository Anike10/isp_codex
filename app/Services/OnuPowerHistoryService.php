<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\CustomerOnuPowerSample;
use App\Models\MikrotikImportedSecret;
use App\Models\PppUsageLog;
use App\Support\Mac;
use App\Support\OnuMatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Periodically snapshots each party's current OLT ONU Rx/Tx optical power
 * into `customer_onu_power_samples` so the party page can chart the last
 * few days. Sampling interval and retention are operator-set app settings.
 */
class OnuPowerHistoryService
{
    public const INTERVAL_KEY = 'onu_power_history.interval_hours';
    public const RETENTION_KEY = 'onu_power_history.retention_days';
    public const SHOW_RX_KEY = 'onu_power_history.show_rx';
    public const SHOW_TX_KEY = 'onu_power_history.show_tx';

    public function intervalHours(): int
    {
        return max(1, (int) AppSetting::value(self::INTERVAL_KEY, '1'));
    }

    public function retentionDays(): int
    {
        return max(1, (int) AppSetting::value(self::RETENTION_KEY, '7'));
    }

    /** Whether the party-page graph draws the Rx series (default on). */
    public function showRx(): bool
    {
        return AppSetting::value(self::SHOW_RX_KEY, '1') === '1';
    }

    /** Whether the party-page graph draws the Tx series (default off). */
    public function showTx(): bool
    {
        return AppSetting::value(self::SHOW_TX_KEY, '0') === '1';
    }

    public function setIntervalHours(int $hours): void
    {
        AppSetting::setValue(self::INTERVAL_KEY, (string) max(1, min(168, $hours)));
    }

    public function setRetentionDays(int $days): void
    {
        AppSetting::setValue(self::RETENTION_KEY, (string) max(1, min(365, $days)));
    }

    public function setShowRx(bool $show): void
    {
        AppSetting::setValue(self::SHOW_RX_KEY, $show ? '1' : '0');
    }

    public function setShowTx(bool $show): void
    {
        AppSetting::setValue(self::SHOW_TX_KEY, $show ? '1' : '0');
    }

    /** True when the newest sample is older than the configured interval. */
    public function isDue(): bool
    {
        $latest = CustomerOnuPowerSample::max('sampled_at');

        return $latest === null
            || \Illuminate\Support\Carbon::parse($latest)->addHours($this->intervalHours())->lte(now());
    }

    /**
     * Snapshot the current ONU reading for every party that has a last-seen
     * device MAC. Uses whatever the OLT poll last stored — it does not poll
     * the OLTs itself (the `olt:auto-refresh` drip keeps those fresh).
     *
     * @return array{customers: int, sampled: int, macs_backfilled: int}
     */
    public function capture(): array
    {
        if (! Schema::hasTable('olt_onus') || ! Schema::hasTable('customer_onu_power_samples')) {
            return ['customers' => 0, 'sampled' => 0, 'macs_backfilled' => 0];
        }

        $macsBackfilled = $this->backfillMissingCustomerMacs();
        $now = now();
        $sampled = 0;
        $seen = 0;

        Customer::query()
            ->whereNotNull('last_connected_mac')
            ->select('id', 'last_connected_mac')
            ->chunkById(500, function ($customers) use ($now, &$sampled, &$seen): void {
                $byMac = OnuMatcher::byMac($customers->pluck('last_connected_mac'));
                if ($byMac === []) {
                    return;
                }

                $rows = [];
                foreach ($customers as $customer) {
                    $seen++;
                    $onu = $byMac[mb_strtolower(trim((string) $customer->last_connected_mac))] ?? null;
                    if (! $onu || ($onu->rx_power_dbm === null && $onu->tx_power_dbm === null)) {
                        continue;
                    }

                    $rows[] = [
                        'customer_id' => $customer->id,
                        'olt_onu_id' => $onu->id,
                        'rx_power_dbm' => $onu->rx_power_dbm,
                        'tx_power_dbm' => $onu->tx_power_dbm,
                        'status' => $onu->status,
                        'sampled_at' => $now,
                        'created_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    CustomerOnuPowerSample::query()->insert($rows);
                    $sampled += count($rows);
                }
            });

        return ['customers' => $seen, 'sampled' => $sampled, 'macs_backfilled' => $macsBackfilled];
    }

    /**
     * Repair older parties whose last-connected MAC was never copied from the
     * router. Prefer a webhook row already linked to the party, then a linked
     * imported secret; finally use same-username data only when it belongs to
     * one of the party's assigned routers.
     */
    public function backfillMissingCustomerMacs(): int
    {
        if (! Schema::hasTable('customers')) {
            return 0;
        }

        $hasUsageLogs = Schema::hasTable('ppp_usage_logs');
        $hasImportedSecrets = Schema::hasTable('mikrotik_imported_secrets');

        if (! $hasUsageLogs && ! $hasImportedSecrets) {
            return 0;
        }

        $updated = 0;

        Customer::query()
            ->where(function ($query): void {
                $query->whereNull('last_connected_mac')->orWhere('last_connected_mac', '');
            })
            ->where(function ($query): void {
                $query->whereNotNull('connection_id')->orWhereNotNull('mikrotik_username');
            })
            ->with('mikrotikRouters:id')
            ->select('id', 'connection_id', 'mikrotik_username', 'mikrotik_router_id', 'last_connected_mac', 'last_connected_at')
            ->chunkById(500, function (Collection $customers) use ($hasUsageLogs, $hasImportedSecrets, &$updated): void {
                $ids = $customers->modelKeys();
                $identifiers = $customers
                    ->flatMap(fn (Customer $customer): array => [$customer->connection_id, $customer->mikrotik_username])
                    ->map(fn ($identifier): string => mb_strtolower(trim((string) $identifier)))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $directLogs = collect();
                $logsByIdentifier = collect();
                if ($hasUsageLogs) {
                    $directLogIds = PppUsageLog::query()
                        ->whereIn('customer_id', $ids)
                        ->whereNotNull('caller_id')
                        ->selectRaw('MAX(id) AS id')
                        ->groupBy('customer_id')
                        ->pluck('id');
                    $directLogs = PppUsageLog::query()
                        ->whereIn('id', $directLogIds)
                        ->get(['id', 'customer_id', 'mikrotik_router_id', 'username', 'caller_id', 'disconnected_at'])
                        ->keyBy('customer_id');

                    if ($identifiers !== []) {
                        $namedLogIds = PppUsageLog::query()
                            ->whereIn(DB::raw('lower(username)'), $identifiers)
                            ->whereNotNull('caller_id')
                            ->selectRaw('MAX(id) AS id')
                            ->groupBy(DB::raw('lower(username)'), 'mikrotik_router_id')
                            ->pluck('id');
                        $logsByIdentifier = PppUsageLog::query()
                            ->whereIn('id', $namedLogIds)
                            ->get(['id', 'customer_id', 'mikrotik_router_id', 'username', 'caller_id', 'disconnected_at'])
                            ->groupBy(fn (PppUsageLog $log): string => mb_strtolower(trim((string) $log->username)));
                    }
                }

                $directSecrets = collect();
                $secretsByIdentifier = collect();
                if ($hasImportedSecrets) {
                    $directSecretIds = MikrotikImportedSecret::query()
                        ->whereIn('customer_id', $ids)
                        ->whereNotNull('device_mac')
                        ->selectRaw('MAX(id) AS id')
                        ->groupBy('customer_id')
                        ->pluck('id');
                    $directSecrets = MikrotikImportedSecret::query()
                        ->whereIn('id', $directSecretIds)
                        ->get(['id', 'customer_id', 'mikrotik_router_id', 'name', 'device_mac', 'imported_at'])
                        ->keyBy('customer_id');

                    if ($identifiers !== []) {
                        $namedSecretIds = MikrotikImportedSecret::query()
                            ->whereIn(DB::raw('lower(name)'), $identifiers)
                            ->whereNotNull('device_mac')
                            ->selectRaw('MAX(id) AS id')
                            ->groupBy(DB::raw('lower(name)'), 'mikrotik_router_id')
                            ->pluck('id');
                        $secretsByIdentifier = MikrotikImportedSecret::query()
                            ->whereIn('id', $namedSecretIds)
                            ->get(['id', 'customer_id', 'mikrotik_router_id', 'name', 'device_mac', 'imported_at'])
                            ->groupBy(fn (MikrotikImportedSecret $secret): string => mb_strtolower(trim((string) $secret->name)));
                    }
                }

                foreach ($customers as $customer) {
                    $candidate = $directLogs->get($customer->id);
                    $mac = Mac::colon($candidate?->caller_id);
                    $seenAt = $candidate?->disconnected_at;

                    if ($mac === null) {
                        $candidate = $directSecrets->get($customer->id);
                        $mac = Mac::colon($candidate?->device_mac);
                        $seenAt = $candidate?->imported_at;
                    }

                    $routerIds = $customer->mikrotikRouters->modelKeys();
                    if ($routerIds === [] && $customer->mikrotik_router_id) {
                        $routerIds = [(int) $customer->mikrotik_router_id];
                    }

                    if ($mac === null && $routerIds !== []) {
                        foreach (array_unique(array_filter([$customer->mikrotik_username, $customer->connection_id])) as $identifier) {
                            $key = mb_strtolower(trim((string) $identifier));
                            $candidate = collect($logsByIdentifier->get($key, []))
                                ->first(fn (PppUsageLog $log): bool => in_array((int) $log->mikrotik_router_id, $routerIds, true) && Mac::colon($log->caller_id) !== null);
                            if ($candidate) {
                                $mac = Mac::colon($candidate->caller_id);
                                $seenAt = $candidate->disconnected_at;
                                break;
                            }

                            $candidate = collect($secretsByIdentifier->get($key, []))
                                ->first(fn (MikrotikImportedSecret $secret): bool => in_array((int) $secret->mikrotik_router_id, $routerIds, true) && Mac::colon($secret->device_mac) !== null);
                            if ($candidate) {
                                $mac = Mac::colon($candidate->device_mac);
                                $seenAt = $candidate->imported_at;
                                break;
                            }
                        }
                    }

                    if ($mac === null) {
                        continue;
                    }

                    $values = ['last_connected_mac' => strtoupper($mac)];
                    if ($seenAt && (! $customer->last_connected_at || Carbon::parse($seenAt)->gt($customer->last_connected_at))) {
                        $values['last_connected_at'] = $seenAt;
                    }
                    $customer->forceFill($values)->save();
                    $updated++;
                }
            });

        return $updated;
    }

    /** Delete samples older than the retention window. */
    public function prune(): int
    {
        if (! Schema::hasTable('customer_onu_power_samples')) {
            return 0;
        }

        return CustomerOnuPowerSample::query()
            ->where('sampled_at', '<', now()->subDays($this->retentionDays()))
            ->delete();
    }
}
