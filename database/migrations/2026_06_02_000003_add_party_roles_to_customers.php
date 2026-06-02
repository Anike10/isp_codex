<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_customer')->default(true)->after('account_balance');
            $table->boolean('is_vendor')->default(false)->after('is_customer');
        });

        DB::table('customers')->update([
            'is_customer' => true,
            'is_vendor' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['is_customer', 'is_vendor']);
        });
    }
};
