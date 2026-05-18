<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_protocol_profiles', function (Blueprint $table) {
            $table->string('vlan_write_context_command')->nullable()->after('default_onu_mac_command');
            $table->string('vlan_write_command')->nullable()->after('vlan_write_context_command');
            $table->string('save_config_command')->nullable()->after('vlan_write_command');
        });

        DB::table('olt_protocol_profiles')
            ->where('key', 'hsgq_epon')
            ->update([
                'vlan_write_context_command' => 'interface onu {pon_port}/{onu_id}',
                'vlan_write_command' => 'port-vlan {port} mode tag {vlan} pri {priority}',
                'save_config_command' => 'save',
                'updated_at' => now(),
            ]);

        DB::table('olt_protocol_profiles')
            ->where('key', 'hsgq_gpon')
            ->update([
                'vlan_write_context_command' => 'interface ont {pon_port}/{onu_id}',
                'vlan_write_command' => null,
                'save_config_command' => 'save',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('olt_protocol_profiles', function (Blueprint $table) {
            $table->dropColumn(['vlan_write_context_command', 'vlan_write_command', 'save_config_command']);
        });
    }
};
