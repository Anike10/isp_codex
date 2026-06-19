<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_by')->nullable()->index();
            $table->string('entry_by_type')->nullable()->index();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('converted_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('quotation_no')->unique();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('billing_month', 7);
            $table->string('invoice_type')->default('product');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('discount_type')->default('amount');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('vat', 12, 2)->default(0);
            $table->string('vat_type')->default('amount');
            $table->decimal('vat_value', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->text('payment_note')->nullable();
            $table->text('public_note')->nullable();
            $table->boolean('show_public_note')->default(false);
            $table->text('private_note')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_by')->nullable()->index();
            $table->string('entry_by_type')->nullable()->index();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_type')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('serial_numbers')->nullable();
            $table->unsignedInteger('serialless_quantity')->default(0);
            $table->unsignedInteger('warranty_days')->nullable();
            $table->unsignedInteger('service_guarantee_days')->nullable();
            $table->date('service_guarantee_until')->nullable();
            $table->text('service_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
