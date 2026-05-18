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
            $table->string('brand')->default('HSGQ')->after('name');
            $table->string('protocol_profile')->default('hsgq_epon')->after('brand')->index();
        });

        DB::table('olt_devices')
            ->whereNull('brand')
            ->orWhere('brand', '')
            ->update(['brand' => 'HSGQ']);

        DB::table('olt_devices')
            ->whereNull('protocol_profile')
            ->orWhere('protocol_profile', '')
            ->update(['protocol_profile' => 'hsgq_epon']);
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->dropColumn(['brand', 'protocol_profile']);
        });
    }
};
