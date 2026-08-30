<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_onu_power_samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('olt_onu_id')->nullable()->constrained('olt_onus')->nullOnDelete();
            $table->decimal('rx_power_dbm', 6, 2)->nullable();
            $table->decimal('tx_power_dbm', 6, 2)->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamp('sampled_at')->index();
            $table->timestamp('created_at')->nullable();

            $table->index(['customer_id', 'sampled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_onu_power_samples');
    }
};
