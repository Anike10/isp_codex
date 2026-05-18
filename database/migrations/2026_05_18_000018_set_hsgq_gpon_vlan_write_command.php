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
                'vlan_write_context_command' => null,
                'vlan_write_command' => 'service-port {service_port} modify vlan {vlan}',
                'save_config_command' => 'save',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('olt_protocol_profiles')
            ->where('key', 'hsgq_gpon')
            ->update([
                'vlan_write_context_command' => 'interface ont {pon_port}/{onu_id}',
                'vlan_write_command' => null,
                'updated_at' => now(),
            ]);
    }
};
