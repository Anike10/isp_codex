<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bkash_sms_payments', function (Blueprint $table): void {
            // Admin who manually paid this SMS from the list/detail page.
            $table->string('paid_by_name')->nullable()->after('entry_by');
        });
    }

    public function down(): void
    {
        Schema::table('bkash_sms_payments', function (Blueprint $table): void {
            $table->dropColumn('paid_by_name');
        });
    }
};
