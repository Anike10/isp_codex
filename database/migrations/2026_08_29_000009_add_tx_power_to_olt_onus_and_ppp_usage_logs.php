<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_onus', function (Blueprint $table): void {
            // ONU transmit (send) optical power, when the OLT reports it.
            $table->decimal('tx_power_dbm', 6, 2)->nullable()->after('rx_power_dbm');
        });

        Schema::table('ppp_usage_logs', function (Blueprint $table): void {
            $table->decimal('tx_power_dbm', 6, 2)->nullable()->after('rx_power_dbm');
        });
    }

    public function down(): void
    {
        Schema::table('olt_onus', fn (Blueprint $table) => $table->dropColumn('tx_power_dbm'));
        Schema::table('ppp_usage_logs', fn (Blueprint $table) => $table->dropColumn('tx_power_dbm'));
    }
};
