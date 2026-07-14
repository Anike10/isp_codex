<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_maintenance_logs', function (Blueprint $table): void {
            $table->string('work_name')->nullable()->after('maintenance_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_maintenance_logs', function (Blueprint $table): void {
            $table->dropColumn('work_name');
        });
    }
};
