<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            // Per-party price for this package. Null means "use the package's
            // own monthly price". Used everywhere the party is billed.
            $table->decimal('custom_price', 12, 2)->nullable()->after('internet_package_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn('custom_price');
        });
    }
};
