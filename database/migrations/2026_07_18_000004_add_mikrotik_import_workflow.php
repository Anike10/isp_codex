<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('address');
        });

        Schema::create('mikrotik_imported_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_router_id')->constrained()->cascadeOnDelete();
            $table->string('routeros_id');
            $table->string('name');
            $table->string('local_address')->nullable();
            $table->string('remote_address')->nullable();
            $table->string('rate_limit')->nullable();
            $table->boolean('disabled')->default(false);
            $table->text('source_note')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();
            $table->unique(['mikrotik_router_id', 'routeros_id']);
        });

        Schema::create('mikrotik_imported_ip_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_router_id')->constrained()->cascadeOnDelete();
            $table->string('routeros_id');
            $table->string('name');
            $table->text('ranges')->nullable();
            $table->string('next_pool')->nullable();
            $table->text('source_note')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();
            $table->unique(['mikrotik_router_id', 'routeros_id']);
        });

        Schema::create('mikrotik_imported_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('routeros_id');
            $table->string('name');
            $table->text('password')->nullable();
            $table->string('service')->nullable();
            $table->string('profile')->nullable();
            $table->string('local_address')->nullable();
            $table->string('remote_address')->nullable();
            $table->boolean('disabled')->default(false);
            $table->text('router_comment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();
            $table->unique(['mikrotik_router_id', 'routeros_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_imported_secrets');
        Schema::dropIfExists('mikrotik_imported_ip_pools');
        Schema::dropIfExists('mikrotik_imported_profiles');
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('notes'));
    }
};
