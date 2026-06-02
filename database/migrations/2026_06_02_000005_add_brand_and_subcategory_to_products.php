<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('sku');
            $table->string('subcategory')->nullable()->after('category');
            $table->index(['brand', 'category', 'subcategory']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['brand', 'category', 'subcategory']);
            $table->dropColumn(['brand', 'subcategory']);
        });
    }
};
