<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type')->default('payment');
            $table->decimal('amount', 10, 2);
            $table->date('allocated_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('direction');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->date('transaction_date');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        DB::table('payments')
            ->orderBy('id')
            ->get()
            ->each(function ($payment): void {
                DB::table('payment_allocations')->insert([
                    'customer_id' => $payment->customer_id,
                    'invoice_id' => $payment->invoice_id,
                    'payment_id' => $payment->id,
                    'source_type' => 'payment',
                    'amount' => $payment->amount,
                    'allocated_at' => $payment->payment_date,
                    'note' => 'Backfilled from existing payment record.',
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ]);
            });

        DB::table('customers')
            ->where('account_balance', '>', 0)
            ->orderBy('id')
            ->get()
            ->each(function ($customer): void {
                DB::table('customer_balance_transactions')->insert([
                    'customer_id' => $customer->id,
                    'payment_id' => null,
                    'payment_account_id' => null,
                    'payment_method' => 'opening',
                    'direction' => 'credit',
                    'amount' => $customer->account_balance,
                    'balance_after' => $customer->account_balance,
                    'transaction_date' => now()->toDateString(),
                    'reference' => 'Opening advance balance',
                    'note' => 'Backfilled from existing customer account balance.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_balance_transactions');
        Schema::dropIfExists('payment_allocations');
    }
};
