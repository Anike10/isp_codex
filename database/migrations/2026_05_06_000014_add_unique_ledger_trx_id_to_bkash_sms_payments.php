<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bkash_sms_payments', function (Blueprint $table) {
            $table->string('ledger_trx_id')->nullable()->unique()->after('trx_id');
        });

        DB::table('bkash_sms_payments')
            ->whereNotNull('trx_id')
            ->where('status', '!=', 'duplicate')
            ->orderBy('id')
            ->get(['id', 'trx_id'])
            ->unique('trx_id')
            ->each(function ($smsPayment): void {
                DB::table('bkash_sms_payments')->where('id', $smsPayment->id)->update([
                    'ledger_trx_id' => $smsPayment->trx_id,
                ]);
            });

        DB::table('bkash_sms_payments')
            ->whereNotNull('trx_id')
            ->whereNull('ledger_trx_id')
            ->where('status', '!=', 'duplicate')
            ->update([
                'status' => 'duplicate',
                'message' => 'Duplicate TrxID. Ledger was not updated.',
            ]);
    }

    public function down(): void
    {
        Schema::table('bkash_sms_payments', function (Blueprint $table) {
            $table->dropColumn('ledger_trx_id');
        });
    }
};
