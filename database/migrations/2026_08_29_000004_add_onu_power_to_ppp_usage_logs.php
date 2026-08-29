<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppp_usage_logs', function (Blueprint $table): void {
            // PPPoE client MAC from RouterOS $"caller-id"; used to line the
            // disconnect up with an OLT ONU and, as a fallback, a customer.
            $table->string('caller_id')->nullable()->after('username')->index();
            $table->foreignId('olt_onu_id')->nullable()->after('customer_id')->constrained('olt_onus')->nullOnDelete();
            // Last known ONU receiving optical power at the time of the drop.
            $table->decimal('rx_power_dbm', 6, 2)->nullable()->after('upload_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('ppp_usage_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('olt_onu_id');
            $table->dropColumn(['caller_id', 'rx_power_dbm']);
        });
    }
};
