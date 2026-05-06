<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->unsignedInteger('pppoe_sync_interval_minutes')->default(10)->after('api_port');
            $table->timestamp('last_pppoe_sync_at')->nullable()->after('last_connection_message');
            $table->string('inactive_pppoe_profile')->default('inactive')->after('last_pppoe_sync_at');
            $table->text('last_pppoe_sync_summary')->nullable()->after('inactive_pppoe_profile');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->dropColumn([
                'pppoe_sync_interval_minutes',
                'last_pppoe_sync_at',
                'inactive_pppoe_profile',
                'last_pppoe_sync_summary',
            ]);
        });
    }
};
