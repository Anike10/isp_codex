<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concession_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('subscription_id')->nullable()->index();
            $table->unsignedBigInteger('internet_package_id')->nullable()->index();

            // The admin who performed the concession.
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();

            // grace_period | validity_override | quick_activate | force_active
            // | force_inactive | mark_special | unmark_special
            $table->string('action_type')->index();
            $table->text('reason')->nullable();

            $table->unsignedInteger('free_days')->nullable();
            $table->date('previous_valid_until')->nullable();
            $table->date('new_valid_until')->nullable();

            // Snapshots so the report stays correct even if the package price
            // changes later.
            $table->decimal('package_monthly_price', 12, 2)->nullable();
            $table->decimal('daily_rate', 12, 4)->nullable();

            // Money the concession is worth (prorated days x daily rate).
            $table->decimal('estimated_value', 12, 2)->default(0);

            // final = value is settled. pending = an open force-active period
            // whose value keeps growing until it is closed.
            $table->string('value_status')->default('final')->index();
            $table->timestamp('closed_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action_type']);
            $table->index(['action_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concession_logs');
    }
};
