<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_protocol_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('brand')->nullable();
            $table->string('pon_interface_command')->default('interface epon {pon_port}');
            $table->string('onu_context_command')->nullable();
            $table->boolean('supports_vlan_polling')->default(false);
            $table->boolean('supports_mac_polling')->default(false);
            $table->text('default_read_context_commands')->nullable();
            $table->string('default_onu_status_command')->nullable();
            $table->string('default_onu_power_command')->nullable();
            $table->string('default_onu_alarm_command')->nullable();
            $table->string('default_onu_vlan_command')->nullable();
            $table->string('default_onu_mac_command')->nullable();
            $table->timestamps();
        });

        DB::table('olt_protocol_profiles')->insert([
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
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'hsgq_gpon',
                'label' => 'HSGQ GPON OLT',
                'brand' => 'HSGQ',
                'pon_interface_command' => 'interface gpon {pon_port}',
                'onu_context_command' => null,
                'supports_vlan_polling' => false,
                'supports_mac_polling' => false,
                'default_read_context_commands' => "enable\nconfig",
                'default_onu_status_command' => 'show ont-info all',
                'default_onu_power_command' => 'show ont-optical all',
                'default_onu_alarm_command' => null,
                'default_onu_vlan_command' => null,
                'default_onu_mac_command' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'generic_epon',
                'label' => 'Generic EPON OLT',
                'brand' => null,
                'pon_interface_command' => 'interface epon {pon_port}',
                'onu_context_command' => null,
                'supports_vlan_polling' => false,
                'supports_mac_polling' => false,
                'default_read_context_commands' => null,
                'default_onu_status_command' => null,
                'default_onu_power_command' => null,
                'default_onu_alarm_command' => null,
                'default_onu_vlan_command' => null,
                'default_onu_mac_command' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_protocol_profiles');
    }
};
