<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['payment_method', 'account_number']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_account_id')->nullable()->after('payment_method')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_account_id');
        });

        Schema::dropIfExists('payment_accounts');
    }
};
