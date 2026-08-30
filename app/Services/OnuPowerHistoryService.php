<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\CustomerOnuPowerSample;
use App\Support\OnuMatcher;
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

    public function intervalHours(): int
    {
        return max(1, (int) AppSetting::value(self::INTERVAL_KEY, '1'));
    }

    public function retentionDays(): int
    {
        return max(1, (int) AppSetting::value(self::RETENTION_KEY, '7'));
    }

    public function setIntervalHours(int $hours): void
    {
        AppSetting::setValue(self::INTERVAL_KEY, (string) max(1, min(168, $hours)));
    }

    public function setRetentionDays(int $days): void
    {
        AppSetting::setValue(self::RETENTION_KEY, (string) max(1, min(365, $days)));
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
     * @return array{customers: int, sampled: int}
     */
    public function capture(): array
    {
        if (! Schema::hasTable('olt_onus') || ! Schema::hasTable('customer_onu_power_samples')) {
            return ['customers' => 0, 'sampled' => 0];
        }

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

        return ['customers' => $seen, 'sampled' => $sampled];
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
