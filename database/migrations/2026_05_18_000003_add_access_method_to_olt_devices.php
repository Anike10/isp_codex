<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->string('access_method')->default('ssh')->after('host')->index();
        });
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table) {
            $table->dropColumn('access_method');
        });
    }
};
