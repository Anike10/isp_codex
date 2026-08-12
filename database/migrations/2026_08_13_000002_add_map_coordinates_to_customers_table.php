<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('map_latitude', 11, 8)->nullable()->after('address');
            $table->decimal('map_longitude', 11, 8)->nullable()->after('map_latitude');
            $table->index(['map_latitude', 'map_longitude'], 'customers_map_coordinates_index');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_map_coordinates_index');
            $table->dropColumn(['map_latitude', 'map_longitude']);
        });
    }
};
