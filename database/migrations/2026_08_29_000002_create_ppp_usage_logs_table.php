<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppp_usage_logs', function (Blueprint $table): void {
            $table->id();
            // Resolved from the webhook's router_id; kept nullable so a payload
            // from a router the app does not know still lands somewhere.
            $table->foreignId('mikrotik_router_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username')->index();
            $table->string('reported_router_id')->nullable();
            $table->string('uptime')->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->unsignedBigInteger('download_bytes')->default(0);
            $table->unsignedBigInteger('upload_bytes')->default(0);
            $table->json('payload')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->index(['mikrotik_router_id', 'username']);
            $table->index('disconnected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppp_usage_logs');
    }
};
