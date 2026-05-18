<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_onus', function (Blueprint $table) {
            $table->json('learned_macs')->nullable()->after('port_vlans');
        });

        Schema::table('olt_devices', function (Blueprint $table) {
            $table->string('onu_mac_command')->nullable()->after('onu_vlan_command');
        });

        DB::table('olt_devices')
            ->whereNull('onu_mac_command')
            ->update(['onu_mac_command' => 'show mac-address epon all']);
    }

    public function down(): void
    {
        Schema::table('olt_onus', function (Blueprint $table) {
            $table->dropColumn('learned_macs');
        });

        Schema::table('olt_devices', function (Blueprint $table) {
            $table->dropColumn('onu_mac_command');
        });
    }
};
