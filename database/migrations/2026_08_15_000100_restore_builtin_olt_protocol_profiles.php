<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('olt_protocol_profiles')) {
            return;
        }

        $timestamp = now();

        DB::table('olt_protocol_profiles')->insertOrIgnore([
            [
                'key' => 'hsgq_epon',
                'label' => 'HSGQ EPON OLT',
                'brand' => 'HSGQ',
                'pon_interface_command' => 'interface epon {pon_port}',
                'onu_context_command' => 'interface onu {pon_port}/{onu_id}',
                'supports_vlan_polling' => true,
                'supports_mac_polling' => true,
                'default_read_context_commands' => "enable\nconfig",
                'default_onu_status_command' => 'show onu-info all',
                'default_onu_power_command' => 'show optical-info',
                'default_onu_alarm_command' => 'show onu-info-alarm {onu_id}',
                'default_onu_vlan_command' => 'show port-vlan',
                'default_onu_mac_command' => 'show mac-address epon all',
                'vlan_write_context_command' => 'interface onu {pon_port}/{onu_id}',
                'vlan_write_command' => 'port-vlan {port} mode tag {vlan} pri {priority}',
                'port_admin_context_command' => null,
                'port_admin_command' => null,
                'save_config_command' => 'save',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'key' => 'hsgq_gpon',
                'label' => 'HSGQ GPON OLT',
                'brand' => 'HSGQ',
                'pon_interface_command' => 'interface gpon {pon_port}',
                'onu_context_command' => 'interface ont {pon_port}/{onu_id}',
                'supports_vlan_polling' => true,
                'supports_mac_polling' => true,
                'default_read_context_commands' => 'config',
                'default_onu_status_command' => 'show ont-info all',
                'default_onu_power_command' => 'show ont-optical all',
                'default_onu_alarm_command' => 'show ont-info {onu_id}',
                'default_onu_vlan_command' => 'show service-port all',
                'default_onu_mac_command' => 'show mac-address all',
                'vlan_write_context_command' => 'interface gpon {pon_port}',
                'vlan_write_command' => 'ont port native-vlan {onu_id} eth {port} vlan {vlan} {priority}',
                'port_admin_context_command' => 'interface gpon {pon_port}',
                'port_admin_command' => 'ont port attribute {onu_id} eth {port} admin-status {state}',
                'save_config_command' => 'save',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }

    public function down(): void
    {
        // Preserve profiles because operators may customize them after insertion.
    }
};
