<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_sync_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_key', 64)->unique();
            $table->foreignId('mikrotik_router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username')->nullable();
            $table->string('issue_type');
            $table->string('expected_profile')->nullable();
            $table->string('actual_profile')->nullable();
            $table->text('details')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            // DATETIME avoids MariaDB's implicit-default rules for multiple
            // non-null TIMESTAMP columns while preserving Laravel date casts.
            $table->dateTime('first_detected_at');
            $table->dateTime('last_detected_at');
            $table->dateTime('last_attempted_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['resolved_at', 'first_detected_at']);
            $table->index(['mikrotik_router_id', 'resolved_at']);
            $table->index(['customer_id', 'resolved_at']);
        });

        Schema::create('mikrotik_sync_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_sync_issue_id')->nullable()->constrained('mikrotik_sync_issues')->nullOnDelete();
            $table->foreignId('mikrotik_router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username')->nullable();
            $table->string('context');
            $table->text('error_message');
            $table->dateTime('attempted_at');
            $table->timestamps();

            $table->index(['mikrotik_router_id', 'attempted_at']);
            $table->index(['customer_id', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_sync_failures');
        Schema::dropIfExists('mikrotik_sync_issues');
    }
};
