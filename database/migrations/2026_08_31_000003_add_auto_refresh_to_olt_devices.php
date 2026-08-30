<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olt_devices', function (Blueprint $table): void {
            // Daily drip refresh: the scheduler picks the single most-overdue
            // active OLT each hour. 0 = never auto-refresh this OLT.
            $table->unsignedSmallInteger('auto_refresh_interval_hours')->default(24)->after('last_polled_at');
            $table->timestamp('last_auto_refresh_at')->nullable()->after('auto_refresh_interval_hours');
        });
    }

    public function down(): void
    {
        Schema::table('olt_devices', function (Blueprint $table): void {
            $table->dropColumn(['auto_refresh_interval_hours', 'last_auto_refresh_at']);
        });
    }
};
