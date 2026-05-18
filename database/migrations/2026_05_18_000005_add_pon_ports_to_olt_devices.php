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
            $table->string('pon_ports')->default('1,2,3,4,5,6,7,8')->after('read_context_commands');
        });

        DB::table('olt_devices')
            ->whereNull('pon_ports')
            ->orWhere('pon_ports', '')
            ->update(['pon_ports' => '1,2,3,4,5,6,7,8']);

        DB::table('olt_devices')
            ->where('read_context_commands', "enable\nconfig\ninterface epon 1")
            ->update(['read_context_commands' => "enable\nconfig"]);
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->dropColumn('pon_ports');
        });
    }
};
