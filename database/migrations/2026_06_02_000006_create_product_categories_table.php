<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['parent_id', 'name']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_category_id')->nullable()->after('brand')->constrained('product_categories')->nullOnDelete();
        });

        $paths = DB::table('products')
            ->select('category', 'subcategory')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->get();

        $parentIds = [];

        foreach ($paths as $path) {
            $parentId = $parentIds[$path->category] ?? DB::table('product_categories')
                ->whereNull('parent_id')
                ->where('name', $path->category)
                ->value('id');

            if (! $parentId) {
                $parentId = DB::table('product_categories')->insertGetId([
                    'name' => $path->category,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $parentIds[$path->category] = $parentId;

            $leafId = $parentId;

            if ($path->subcategory) {
                $leafId = DB::table('product_categories')
                    ->where('parent_id', $parentId)
                    ->where('name', $path->subcategory)
                    ->value('id');

                if (! $leafId) {
                    $leafId = DB::table('product_categories')->insertGetId([
                        'parent_id' => $parentId,
                        'name' => $path->subcategory,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('products')
                ->where('category', $path->category)
                ->when($path->subcategory, fn ($query) => $query->where('subcategory', $path->subcategory))
                ->when(! $path->subcategory, fn ($query) => $query->where(fn ($query) => $query->whereNull('subcategory')->orWhere('subcategory', '')))
                ->update(['product_category_id' => $leafId]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_category_id');
        });

        Schema::dropIfExists('product_categories');
    }
};
