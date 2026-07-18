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
            $table->decimal('reseller_commission_percent', 5, 2)->default(0)->after('reseller_daily_payment_limit');
        });

        Schema::create('reseller_commission_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('old_percent', 5, 2)->nullable();
            $table->decimal('new_percent', 5, 2);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['reseller_id', 'changed_at']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->after('customer_id')->constrained('customers')->nullOnDelete();
            $table->decimal('reseller_commission_percent', 5, 2)->default(0)->after('reseller_id');
            $table->decimal('reseller_commission_amount', 12, 2)->default(0)->after('reseller_commission_percent');
            $table->decimal('gross_total', 12, 2)->default(0)->after('reseller_commission_amount');
        });

        DB::table('invoices')->update(['gross_total' => DB::raw('total')]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reseller_id');
            $table->dropColumn(['reseller_commission_percent', 'reseller_commission_amount', 'gross_total']);
        });
        Schema::dropIfExists('reseller_commission_histories');
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('reseller_commission_percent'));
    }
};
