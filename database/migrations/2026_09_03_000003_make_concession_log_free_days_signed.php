<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concession_logs', function (Blueprint $table): void {
            $table->integer('free_days')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('concession_logs', function (Blueprint $table): void {
            $table->unsignedInteger('free_days')->nullable()->change();
        });
    }
};
