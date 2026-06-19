<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_by')->nullable()->index();
            $table->string('entry_by_type')->nullable()->index();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        $defaultWarehouseId = DB::table('warehouses')->insertGetId([
            'entry_by' => 'system',
            'entry_by_type' => 'system',
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_default' => true,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::create('product_warehouse_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->foreignId('related_warehouse_id')->nullable()->after('warehouse_id')->constrained('warehouses')->nullOnDelete();
            $table->unsignedInteger('balance_before')->nullable()->after('serialless_quantity');
            $table->unsignedInteger('balance_after')->nullable()->after('balance_before');
        });

        Schema::table('product_serials', function (Blueprint $table): void {
            $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });

        DB::table('products')
            ->where('track_inventory', true)
            ->orderBy('id')
            ->get(['id', 'stock_quantity'])
            ->each(function ($product) use ($defaultWarehouseId, $now): void {
                DB::table('product_warehouse_stocks')->insert([
                    'product_id' => $product->id,
                    'warehouse_id' => $defaultWarehouseId,
                    'quantity' => max(0, (int) $product->stock_quantity),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        DB::table('stock_movements')->update(['warehouse_id' => $defaultWarehouseId]);
        DB::table('product_serials')
            ->where('status', 'in_stock')
            ->update(['warehouse_id' => $defaultWarehouseId]);
    }

    public function down(): void
    {
        Schema::table('product_serials', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('related_warehouse_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn(['balance_before', 'balance_after']);
        });

        Schema::dropIfExists('product_warehouse_stocks');
        Schema::dropIfExists('warehouses');
    }
};
