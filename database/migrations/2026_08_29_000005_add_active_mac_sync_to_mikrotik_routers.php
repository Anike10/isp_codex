<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            // How often to poll /ppp/active and copy each session's device MAC
            // (caller-id) onto the matching party. Lighter than the full PPPoE
            // sync, so it can run more often.
            $table->unsignedSmallInteger('active_mac_sync_interval_minutes')->default(15)->after('pppoe_sync_interval_minutes');
            $table->timestamp('last_active_mac_sync_at')->nullable()->after('last_pppoe_sync_summary');
            $table->string('last_active_mac_sync_summary')->nullable()->after('last_active_mac_sync_at');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->dropColumn([
                'active_mac_sync_interval_minutes',
                'last_active_mac_sync_at',
                'last_active_mac_sync_summary',
            ]);
        });
    }
};
