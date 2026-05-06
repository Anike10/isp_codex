<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bkash_sms_payments')
            ->whereNotNull('payment_id')
            ->orderBy('id')
            ->get()
            ->each(function ($smsPayment): void {
                $deviceName = $this->extractDeviceName($smsPayment->raw_sms, $smsPayment->sms_sender);

                if (! $deviceName) {
                    return;
                }

                $account = DB::table('payment_accounts')
                    ->where('payment_method', 'bkash')
                    ->where('account_number', 'sms-device:'.Str::slug($deviceName))
                    ->first();

                if (! $account) {
                    DB::table('payment_accounts')->insert([
                        'payment_method' => 'bkash',
                        'account_number' => 'sms-device:'.Str::slug($deviceName),
                        'account_name' => $deviceName,
                        'opening_balance' => 0,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $account = DB::table('payment_accounts')
                        ->where('payment_method', 'bkash')
                        ->where('account_number', 'sms-device:'.Str::slug($deviceName))
                        ->first();
                } else {
                    DB::table('payment_accounts')->where('id', $account->id)->update([
                        'account_name' => $deviceName,
                        'updated_at' => now(),
                    ]);
                }

                if ($account) {
                    DB::table('payments')
                        ->where('id', $smsPayment->payment_id)
                        ->update(['payment_account_id' => $account->id]);
                }
            });
    }

    public function down(): void
    {
        // This data migration intentionally keeps remapped payment accounts.
    }

    private function extractDeviceName(string $rawSms, ?string $smsSender = null): ?string
    {
        if ($smsSender && ! in_array(strtolower(trim($smsSender)), ['bkash', 'b-kash'], true)) {
            return trim($smsSender);
        }

        $lines = collect(preg_split('/\R+/', trim($rawSms)))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        for ($index = $lines->count() - 1; $index >= 0; $index--) {
            $line = $lines[$index];

            if (preg_match('/^(bkash|sim\d*_?|subid|subid[:：]|you have received|trxid|fee tk|balance tk)/i', $line)) {
                continue;
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $line)) {
                continue;
            }

            if (preg_match('/^\d+$/', $line)) {
                continue;
            }

            return $line;
        }

        return null;
    }
};
