<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_user_denials', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });

        Schema::create('user_menu_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('menu_key', 100);
            $table->boolean('allowed')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'menu_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_menu_accesses');
        Schema::dropIfExists('permission_user_denials');
    }
};
