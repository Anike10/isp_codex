<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('fleet_role', 30)->nullable()->after('designation')->index();
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_no')->unique();
            $table->string('name');
            $table->string('vehicle_type')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('chassis_no')->nullable()->unique();
            $table->string('engine_no')->nullable()->unique();
            $table->string('fuel_type', 30)->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->unsignedBigInteger('current_mileage')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_maintenance_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('maintenance_type', 30);
            $table->unsignedInteger('interval_days')->nullable();
            $table->unsignedBigInteger('interval_mileage')->nullable();
            $table->date('last_checked_at')->nullable();
            $table->date('last_changed_at')->nullable();
            $table->unsignedBigInteger('last_service_mileage')->nullable();
            $table->date('next_due_date')->nullable();
            $table->unsignedBigInteger('next_due_mileage')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'name']);
            $table->index(['vehicle_id', 'is_active', 'next_due_date'], 'fleet_maintenance_due_idx');
        });

        Schema::create('vehicle_maintenance_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_item_id')->nullable()->constrained('vehicle_maintenance_items')->nullOnDelete();
            $table->string('action', 30);
            $table->date('service_date');
            $table->unsignedBigInteger('mileage')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->text('details')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'service_date']);
        });

        Schema::create('vehicle_assignments_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('duty_role', 30);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'duty_role', 'end_date'], 'fleet_assignment_vehicle_role_idx');
            $table->index(['employee_id', 'start_date', 'end_date'], 'fleet_assignment_employee_dates_idx');
        });

        Schema::create('vehicle_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 40)->index();
            $table->date('expense_date')->index();
            $table->decimal('amount', 14, 2);
            $table->decimal('quantity', 12, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->unsignedBigInteger('mileage')->nullable();
            $table->string('trip_reference')->nullable();
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'expense_date']);
        });

        $permissionId = DB::table('permissions')->where('name', 'manage_fleet')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'manage_fleet',
                'label' => 'Manage vehicles and fleet',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        if ($adminRoleId && ! DB::table('permission_role')->where(['permission_id' => $permissionId, 'role_id' => $adminRoleId])->exists()) {
            DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => $adminRoleId]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'manage_fleet')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('vehicle_expenses');
        Schema::dropIfExists('vehicle_assignments_history');
        Schema::dropIfExists('vehicle_maintenance_logs');
        Schema::dropIfExists('vehicle_maintenance_items');
        Schema::dropIfExists('vehicles');
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('fleet_role'));
    }
};
