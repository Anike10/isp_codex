<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->timestamp('api_status_since')->nullable()->after('last_api_status');
            $table->timestamp('ping_status_since')->nullable()->after('last_ping_status');
        });

        DB::table('mikrotik_routers')->orderBy('id')->get(['id', 'last_checked_at', 'last_api_status', 'last_ping_status'])->each(function ($router): void {
            $since = $router->last_checked_at ?? now();

            DB::table('mikrotik_routers')->where('id', $router->id)->update([
                'api_status_since' => $router->last_api_status ? $since : null,
                'ping_status_since' => $router->last_ping_status ? $since : null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->dropColumn(['api_status_since', 'ping_status_since']);
        });
    }
};
