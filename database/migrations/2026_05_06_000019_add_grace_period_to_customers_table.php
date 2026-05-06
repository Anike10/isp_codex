<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('grace_until')->nullable()->after('never_suspend');
            $table->unsignedInteger('grace_days')->nullable()->after('grace_until');
            $table->timestamp('grace_used_at')->nullable()->after('grace_days');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['grace_until', 'grace_days', 'grace_used_at']);
        });
    }
};
