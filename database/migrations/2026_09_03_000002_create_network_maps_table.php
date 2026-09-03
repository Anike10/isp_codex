<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_maps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 60)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->boolean('is_test')->default(false)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->foreignId('entry_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // The single map everyone has been drawing on so far.
        $mainId = DB::table('network_maps')->insertGetId([
            'name' => 'Main Network',
            'slug' => 'main',
            'is_test' => false,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // A throwaway map for experiments that must never touch the live topology.
        DB::table('network_maps')->insert([
            'name' => 'Test Map',
            'slug' => 'test',
            'is_test' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('network_map_features', function (Blueprint $table) use ($mainId) {
            $table->foreignId('network_map_id')->nullable()->after('id')
                ->constrained('network_maps')->nullOnDelete();
            $table->index('network_map_id');
        });

        DB::table('network_map_features')->update(['network_map_id' => $mainId]);
    }

    public function down(): void
    {
        Schema::table('network_map_features', function (Blueprint $table) {
            $table->dropConstrainedForeignId('network_map_id');
        });

        Schema::dropIfExists('network_maps');
    }
};
