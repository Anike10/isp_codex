<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_map_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('feature_uuid')->unique();
            $table->string('feature_type', 20)->index(); // node or link
            $table->string('component_type', 30)->index(); // router, splitter, fiber_cable, etc.
            $table->string('name')->nullable()->index();
            $table->json('properties');
            $table->json('geometry');
            $table->decimal('latitude', 11, 8)->nullable()->index();
            $table->decimal('longitude', 11, 8)->nullable()->index();
            $table->decimal('length_meters', 12, 2)->nullable()->index();
            $table->timestamps();

            $table->index(['feature_type', 'component_type']);
        });

        /*
         * If your production database supports GIS extensions, this JSON geometry column can
         * be complemented later with native spatial columns:
         * - MySQL: $table->point('point')->nullable(); $table->lineString('line')->nullable();
         * - PostgreSQL/PostGIS: add geometry(Point, 4326) / geometry(LineString, 4326).
         * The app intentionally stores clean GeoJSON so it works on MySQL and PostgreSQL
         * without requiring a spatial PHP package.
         */
    }

    public function down(): void
    {
        Schema::dropIfExists('network_map_features');
    }
};
