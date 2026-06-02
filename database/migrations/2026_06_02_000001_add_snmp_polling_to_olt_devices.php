<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->boolean('snmp_enabled')->default(false)->after('enable_password');
            $table->string('snmp_version', 8)->default('2c')->after('snmp_enabled');
            $table->unsignedSmallInteger('snmp_port')->default(161)->after('snmp_version');
            $table->text('snmp_community')->nullable()->after('snmp_port');
            $table->unsignedInteger('snmp_timeout_ms')->default(800)->after('snmp_community');
            $table->unsignedTinyInteger('snmp_retries')->default(1)->after('snmp_timeout_ms');
            $table->string('snmp_status_oid_template')->nullable()->after('snmp_retries');
            $table->string('snmp_power_oid_template')->nullable()->after('snmp_status_oid_template');
            $table->decimal('snmp_power_divisor', 8, 2)->default(1)->after('snmp_power_oid_template');
        });
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->dropColumn([
                'snmp_enabled',
                'snmp_version',
                'snmp_port',
                'snmp_community',
                'snmp_timeout_ms',
                'snmp_retries',
                'snmp_status_oid_template',
                'snmp_power_oid_template',
                'snmp_power_divisor',
            ]);
        });
    }
};
