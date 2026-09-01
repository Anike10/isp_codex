<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppp_live_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mikrotik_router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('routeros_session_id');
            $table->string('username')->index();
            $table->string('caller_id')->nullable();
            $table->string('uptime')->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->unsignedBigInteger('download_bytes')->default(0);
            $table->unsignedBigInteger('upload_bytes')->default(0);
            $table->json('payload')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['mikrotik_router_id', 'routeros_session_id'], 'ppp_live_router_session_unique');
            $table->index(['mikrotik_router_id', 'username']);
        });

        Schema::table('ppp_usage_logs', function (Blueprint $table): void {
            $table->string('source')->nullable()->after('disconnect_reason')->index();
            $table->string('routeros_session_id')->nullable()->after('source');
            $table->index(
                ['mikrotik_router_id', 'routeros_session_id'],
                'ppp_usage_router_session_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ppp_usage_logs', function (Blueprint $table): void {
            $table->dropIndex('ppp_usage_router_session_index');
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'routeros_session_id']);
        });

        Schema::dropIfExists('ppp_live_sessions');
    }
};
