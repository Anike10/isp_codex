<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->text('read_context_commands')->nullable()->after('enable_password');
        });

        DB::table('olt_devices')
            ->whereNull('read_context_commands')
            ->update(['read_context_commands' => "enable\nconfig\ninterface epon 1"]);

        DB::table('olt_devices')
            ->where('onu_status_command', 'show onu all')
            ->update(['onu_status_command' => 'show onu-info all']);

        DB::table('olt_devices')
            ->where('onu_power_command', 'show epon optical-transceiver diagnosis')
            ->update(['onu_power_command' => 'show optical-info']);
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->dropColumn('read_context_commands');
        });
    }
};
