<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_accounts', function (Blueprint $table): void {
            // Null means no ceiling. When set, collections may not push the
            // account's live balance above this figure.
            $table->decimal('balance_limit', 12, 2)->nullable()->after('opening_balance');
        });

        Schema::create('account_deposits', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_by')->nullable()->index();
            $table->string('entry_by_type')->nullable();
            $table->foreignId('payment_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deposited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('deposited_at');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['payment_account_id', 'deposited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deposits');

        Schema::table('payment_accounts', function (Blueprint $table): void {
            $table->dropColumn('balance_limit');
        });
    }
};
