<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_payment_method')->nullable()->after('reseller_id');
            $table->foreignId('default_payment_account_id')
                ->nullable()
                ->after('default_payment_method')
                ->constrained('payment_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_payment_account_id');
            $table->dropColumn('default_payment_method');
        });
    }
};
