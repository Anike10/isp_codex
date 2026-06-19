<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table): void {
            $table->unsignedInteger('serialless_quantity')->default(0)->after('quantity');
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->unsignedInteger('serialless_quantity')->default(0)->after('serial_numbers');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->unsignedInteger('serialless_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropColumn('serialless_quantity');
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropColumn('serialless_quantity');
        });

        Schema::table('purchase_bill_items', function (Blueprint $table): void {
            $table->dropColumn('serialless_quantity');
        });
    }
};
