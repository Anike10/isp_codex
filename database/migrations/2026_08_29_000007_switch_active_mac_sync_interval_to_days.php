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
            $table->unsignedSmallInteger('active_mac_sync_interval_days')->default(1)->after('pppoe_sync_interval_days');
        });

        DB::table('mikrotik_routers')->update(['active_mac_sync_interval_days' => 1]);

        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->dropColumn('active_mac_sync_interval_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->unsignedSmallInteger('active_mac_sync_interval_minutes')->default(15)->after('pppoe_sync_interval_days');
        });

        foreach (DB::table('mikrotik_routers')->get(['id', 'active_mac_sync_interval_days']) as $router) {
            DB::table('mikrotik_routers')->where('id', $router->id)->update([
                'active_mac_sync_interval_minutes' => max(5, (int) ($router->active_mac_sync_interval_days ?: 1) * 1440),
            ]);
        }

        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->dropColumn('active_mac_sync_interval_days');
        });
    }
};
