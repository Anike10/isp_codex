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
            $table->string('port_admin_context_command')->nullable()->after('vlan_write_command');
            $table->string('port_admin_command')->nullable()->after('port_admin_context_command');
        });

        Schema::table('olt_onus', function (Blueprint $table) {
            $table->json('port_admin_states')->nullable()->after('port_vlans');
        });

        Schema::create('olt_refresh_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_device_id')->nullable()->constrained('olt_devices')->nullOnDelete();
            $table->string('olt_name');
            $table->string('refresh_mode')->default('full_mac');
            $table->unsignedTinyInteger('pon_port')->nullable();
            $table->string('status')->default('queued')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        DB::table('olt_protocol_profiles')
            ->where('key', 'hsgq_gpon')
            ->update([
                'port_admin_context_command' => 'interface gpon {pon_port}',
                'port_admin_command' => 'ont port attribute {onu_id} eth {port} admin-status {state}',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_refresh_runs');

        Schema::table('olt_onus', function (Blueprint $table) {
            $table->dropColumn('port_admin_states');
        });

        Schema::table('olt_protocol_profiles', function (Blueprint $table) {
            $table->dropColumn(['port_admin_context_command', 'port_admin_command']);
        });
    }
};
