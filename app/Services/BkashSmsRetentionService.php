<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\BkashSmsPayment;

class BkashSmsRetentionService
{
    /** Days to keep any bKash SMS row before automatic deletion. 0 = keep forever. */
    public const RETENTION_KEY = 'bkash_sms_retention_days';

    /** When on, failed SMS that carry no parseable payment (not bKash/Nagad/etc.) are pruned nightly. */
    public const JUNK_KEY = 'bkash_sms_autodelete_junk';

    public function retentionDays(): int
    {
        return max(0, (int) AppSetting::value(self::RETENTION_KEY, '0'));
    }

    public function setRetentionDays(int $days): void
    {
        AppSetting::setValue(self::RETENTION_KEY, (string) max(0, min($days, 3650)));
    }

    public function junkAutoDelete(): bool
    {
        return AppSetting::value(self::JUNK_KEY, '0') === '1';
    }

    public function setJunkAutoDelete(bool $on): void
    {
        AppSetting::setValue(self::JUNK_KEY, $on ? '1' : '0');
    }

    /** Delete every SMS row older than the retention window. Returns rows removed. */
    public function pruneOldRows(): int
    {
        $days = $this->retentionDays();

        if ($days <= 0) {
            return 0;
        }

        return BkashSmsPayment::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /** Delete every row with a failed status. Returns rows removed. */
    public function deleteFailedRows(): int
    {
        return BkashSmsPayment::query()->where('status', 'failed')->delete();
    }

    /**
     * Delete failed rows that never parsed as a payment SMS at all
     * (no TrxID and no amount) — OTPs, promos, and other junk that is
     * not a bKash / Nagad / other provider payment notification.
     */
    public function pruneJunkFailedRows(): int
    {
        return BkashSmsPayment::query()
            ->where('status', 'failed')
            ->whereNull('trx_id')
            ->whereNull('amount')
            ->delete();
    }
}
