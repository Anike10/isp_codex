<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('mikrotik_routers')
            ->where('pppoe_sync_interval_minutes', 10)
            ->update(['pppoe_sync_interval_minutes' => 60]);

        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->unsignedInteger('pppoe_sync_interval_minutes')->default(60)->change();
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->unsignedInteger('pppoe_sync_interval_minutes')->default(10)->change();
        });
    }
};
