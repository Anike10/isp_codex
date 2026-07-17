<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_onus', function (Blueprint $table) {
            $table->unsignedTinyInteger('ethernet_port_count')->nullable()->after('port_admin_states');
        });
    }

    public function down(): void
    {
        Schema::table('olt_onus', function (Blueprint $table) {
            $table->dropColumn('ethernet_port_count');
        });
    }
};
