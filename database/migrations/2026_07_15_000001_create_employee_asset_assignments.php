<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_asset_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_condition', 20)->default('new');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('serialless_quantity')->default(0);
            $table->text('serial_numbers')->nullable();
            $table->date('assigned_at');
            $table->string('purpose')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'assigned_at']);
            $table->index(['product_id', 'source_condition']);
        });

        Schema::create('employee_asset_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_asset_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('serialless_quantity')->default(0);
            $table->text('serial_numbers')->nullable();
            $table->date('returned_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['employee_asset_assignment_id', 'returned_at'], 'asset_returns_assignment_date_idx');
        });

        Schema::create('used_product_warehouse_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('used_product_warehouse_stocks');
        Schema::dropIfExists('employee_asset_returns');
        Schema::dropIfExists('employee_asset_assignments');
    }
};
