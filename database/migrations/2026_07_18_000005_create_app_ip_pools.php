<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_ip_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('ranges');
            $table->string('next_pool')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['mikrotik_router_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_ip_pools');
    }
};
