<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('barcode')->nullable()->unique()->after('sku');
            $table->boolean('track_serial_numbers')->default(false)->after('track_inventory');
            $table->unsignedInteger('warranty_days')->nullable()->after('track_serial_numbers');
        });

        Schema::table('purchase_bill_items', function (Blueprint $table): void {
            $table->unsignedInteger('warranty_days')->nullable()->after('warranty_months');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table): void {
            $table->dropColumn('warranty_days');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['barcode']);
            $table->dropColumn(['barcode', 'track_serial_numbers', 'warranty_days']);
        });
    }
};
