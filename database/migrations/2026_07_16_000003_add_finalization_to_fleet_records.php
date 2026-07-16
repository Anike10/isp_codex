<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_maintenance_logs', function (Blueprint $table): void {
            $table->timestamp('finalized_at')->nullable()->after('youtube_url');
            $table->foreignId('finalized_by')->nullable()->after('finalized_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('vehicle_expenses', function (Blueprint $table): void {
            $table->timestamp('finalized_at')->nullable()->after('description');
            $table->foreignId('finalized_by')->nullable()->after('finalized_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_maintenance_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn('finalized_at');
        });

        Schema::table('vehicle_expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn('finalized_at');
        });
    }
};
