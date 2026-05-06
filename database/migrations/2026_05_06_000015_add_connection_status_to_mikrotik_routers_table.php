<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->string('last_api_status')->nullable()->after('status');
            $table->string('last_ping_status')->nullable()->after('last_api_status');
            $table->unsignedInteger('last_api_latency_ms')->nullable()->after('last_ping_status');
            $table->unsignedInteger('last_ping_latency_ms')->nullable()->after('last_api_latency_ms');
            $table->timestamp('last_checked_at')->nullable()->after('last_ping_latency_ms');
            $table->timestamp('last_online_at')->nullable()->after('last_checked_at');
            $table->timestamp('last_offline_at')->nullable()->after('last_online_at');
            $table->timestamp('last_ping_at')->nullable()->after('last_offline_at');
            $table->text('last_connection_message')->nullable()->after('last_ping_at');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table) {
            $table->dropColumn([
                'last_api_status',
                'last_ping_status',
                'last_api_latency_ms',
                'last_ping_latency_ms',
                'last_checked_at',
                'last_online_at',
                'last_offline_at',
                'last_ping_at',
                'last_connection_message',
            ]);
        });
    }
};
