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
            $table->string('onu_vlan_command')->nullable()->after('onu_alarm_command');
        });

        DB::table('olt_devices')
            ->whereNull('onu_vlan_command')
            ->update(['onu_vlan_command' => 'show port-vlan']);
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->dropColumn('onu_vlan_command');
        });
    }
};
