<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $tables = [
        'users',
        'roles',
        'permissions',
        'internet_packages',
        'customers',
        'subscriptions',
        'invoices',
        'invoice_items',
        'payments',
        'payment_accounts',
        'support_tickets',
        'products',
        'stock_movements',
        'mikrotik_routers',
        'bkash_sms_payments',
        'payment_allocations',
        'customer_balance_transactions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'entry_by')) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'entry_by_type')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->string('entry_by_type')->nullable()->after('entry_by')->index();
                });
            }

            DB::table($tableName)
                ->whereNull('entry_by')
                ->update([
                    'entry_by' => 'system',
                    'entry_by_type' => 'system',
                ]);

            DB::table($tableName)
                ->whereNotNull('entry_by')
                ->whereNull('entry_by_type')
                ->orderBy('id')
                ->get(['id', 'entry_by'])
                ->each(function ($row) use ($tableName): void {
                    DB::table($tableName)->where('id', $row->id)->update([
                        'entry_by_type' => ctype_digit((string) $row->entry_by) ? 'user' : 'system',
                    ]);
                });
        }

        $this->backfillBkashDeviceEntries();
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'entry_by_type')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('entry_by_type');
            });
        }
    }

    private function backfillBkashDeviceEntries(): void
    {
        if (! Schema::hasTable('bkash_sms_payments')) {
            return;
        }

        DB::table('bkash_sms_payments')
            ->orderBy('id')
            ->get(['id', 'raw_sms', 'sms_sender', 'payment_id'])
            ->each(function ($smsPayment): void {
                $deviceName = $this->extractDeviceName($smsPayment->raw_sms, $smsPayment->sms_sender);

                if (! $deviceName) {
                    return;
                }

                DB::table('bkash_sms_payments')->where('id', $smsPayment->id)->update([
                    'entry_by' => $deviceName,
                    'entry_by_type' => 'sms_device',
                ]);

                $account = DB::table('payment_accounts')
                    ->where('payment_method', 'bkash')
                    ->where('account_number', 'sms-device:'.Str::slug($deviceName))
                    ->first();

                if ($account) {
                    DB::table('payment_accounts')->where('id', $account->id)->update([
                        'entry_by' => $deviceName,
                        'entry_by_type' => 'sms_device',
                    ]);
                }

                if ($smsPayment->payment_id) {
                    DB::table('payments')->where('id', $smsPayment->payment_id)->update([
                        'entry_by' => $deviceName,
                        'entry_by_type' => 'sms_device',
                    ]);

                    DB::table('payment_allocations')->where('payment_id', $smsPayment->payment_id)->update([
                        'entry_by' => $deviceName,
                        'entry_by_type' => 'sms_device',
                    ]);

                    DB::table('customer_balance_transactions')->where('payment_id', $smsPayment->payment_id)->update([
                        'entry_by' => $deviceName,
                        'entry_by_type' => 'sms_device',
                    ]);
                }
            });
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
