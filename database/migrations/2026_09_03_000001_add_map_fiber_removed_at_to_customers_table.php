<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a party is soft-deleted its drop fiber is still physically in place, so
 * it stays on the network map as a red "pull the fiber" marker. Once a field
 * operator has removed that fiber they stamp `map_fiber_removed_at`, which
 * takes the marker off the map for good.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->timestamp('map_fiber_removed_at')->nullable()->after('map_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('map_fiber_removed_at');
        });
    }
};
