<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->unsignedSmallInteger('pppoe_sync_interval_days')->default(10)->after('api_port');
        });

        // The old cadence was sub-daily (1–24h); move every existing router to
        // the new day-based default rather than collapsing them all to 1 day.
        DB::table('mikrotik_routers')->update(['pppoe_sync_interval_days' => 10]);

        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->dropColumn('pppoe_sync_interval_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->unsignedInteger('pppoe_sync_interval_minutes')->default(60)->after('api_port');
        });

        foreach (DB::table('mikrotik_routers')->get(['id', 'pppoe_sync_interval_days']) as $router) {
            DB::table('mikrotik_routers')->where('id', $router->id)->update([
                'pppoe_sync_interval_minutes' => max(60, (int) ($router->pppoe_sync_interval_days ?: 10) * 1440),
            ]);
        }

        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->dropColumn('pppoe_sync_interval_days');
        });
    }
};
