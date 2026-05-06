<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkash_sms_payments', function (Blueprint $table) {
            $table->id();
            $table->string('sms_sender')->nullable();
            $table->text('raw_sms');
            $table->string('customer_number')->nullable()->index();
            $table->string('trx_id')->nullable()->unique();
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkash_sms_payments');
    }
};
