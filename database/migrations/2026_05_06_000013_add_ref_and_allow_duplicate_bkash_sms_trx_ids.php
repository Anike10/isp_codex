<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bkash_sms_payments', function (Blueprint $table) {
            $table->dropUnique(['trx_id']);
            $table->index('trx_id');
            $table->string('reference')->nullable()->after('trx_id');
        });
    }

    public function down(): void
    {
        Schema::table('bkash_sms_payments', function (Blueprint $table) {
            $table->dropColumn('reference');
            $table->dropIndex(['trx_id']);
            $table->unique('trx_id');
        });
    }
};
