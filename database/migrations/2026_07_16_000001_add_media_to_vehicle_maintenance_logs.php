<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_maintenance_logs', function (Blueprint $table): void {
            $table->string('youtube_url', 2048)->nullable()->after('details');
        });

        Schema::create('vehicle_maintenance_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_maintenance_log_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_photos');
        Schema::table('vehicle_maintenance_logs', fn (Blueprint $table) => $table->dropColumn('youtube_url'));
    }
};
