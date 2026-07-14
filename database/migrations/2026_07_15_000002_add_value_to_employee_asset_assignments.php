<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_asset_assignments', function (Blueprint $table): void {
            $table->decimal('unit_price', 12, 2)->default(0)->after('quantity');
            $table->decimal('total', 14, 2)->default(0)->after('unit_price');
        });

        DB::statement('UPDATE employee_asset_assignments SET unit_price = COALESCE((SELECT purchase_price FROM products WHERE products.id = employee_asset_assignments.product_id), 0)');
        DB::statement('UPDATE employee_asset_assignments SET total = quantity * unit_price');
    }

    public function down(): void
    {
        Schema::table('employee_asset_assignments', function (Blueprint $table): void {
            $table->dropColumn(['unit_price', 'total']);
        });
    }
};
