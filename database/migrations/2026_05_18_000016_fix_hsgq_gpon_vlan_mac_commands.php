<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('olt_protocol_profiles')
            ->where('key', 'hsgq_gpon')
            ->update([
                'onu_context_command' => 'interface ont {pon_port}/{onu_id}',
                'supports_vlan_polling' => true,
                'supports_mac_polling' => true,
                'default_onu_alarm_command' => 'show ont-info {onu_id}',
                'default_onu_vlan_command' => 'show service-port all',
                'default_onu_mac_command' => 'show mac-address all',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('olt_protocol_profiles')
            ->where('key', 'hsgq_gpon')
            ->update([
                'default_onu_alarm_command' => 'show ont-info-alarm {onu_id}',
                'default_onu_vlan_command' => 'show port-vlan',
                'default_onu_mac_command' => 'show mac-address gpon all',
                'updated_at' => now(),
            ]);
    }
};
