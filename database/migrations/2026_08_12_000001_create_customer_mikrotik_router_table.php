<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_mikrotik_router', function (Blueprint $table): void {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mikrotik_router_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'mikrotik_router_id']);
        });

        $now = now();
        DB::table('customers')
            ->whereNotNull('mikrotik_router_id')
            ->select(['id', 'mikrotik_router_id'])
            ->orderBy('id')
            ->chunkById(500, function ($customers) use ($now): void {
                DB::table('customer_mikrotik_router')->insertOrIgnore(
                    $customers->map(fn ($customer) => [
                        'customer_id' => $customer->id,
                        'mikrotik_router_id' => $customer->mikrotik_router_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_mikrotik_router');
    }
};
