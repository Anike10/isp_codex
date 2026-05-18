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
            $table->string('onu_alarm_command')->nullable()->after('onu_power_command');
        });

        DB::table('olt_devices')
            ->whereNull('onu_alarm_command')
            ->update(['onu_alarm_command' => 'show onu-info-alarm {onu_id}']);
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->dropColumn('onu_alarm_command');
        });
    }
};
