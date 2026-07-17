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
                'vlan_write_context_command' => 'interface gpon {pon_port}',
                'vlan_write_command' => 'ont port native-vlan {onu_id} {port_path} vlan {vlan} {priority}',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('olt_protocol_profiles')
            ->where('key', 'hsgq_gpon')
            ->update([
                'vlan_write_context_command' => 'interface gpon {pon_port}',
                'vlan_write_command' => 'ont port native-vlan {onu_id} eth {port} vlan {vlan} {priority}',
                'updated_at' => now(),
            ]);
    }
};
