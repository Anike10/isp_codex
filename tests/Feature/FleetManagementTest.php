<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignmentHistory;
use App\Models\VehicleMaintenanceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_dashboard_and_permission_protection_work(): void
    {
        [$user, $vehicle] = $this->fixture();

        $this->get(route('fleet.index'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('fleet.index'))
            ->assertOk()
            ->assertSee('Vehicle & Fleet Management', false)
            ->assertSee('<details class="nav-group">', false)
            ->assertSee(route('fleet.reports'), false)
            ->assertSee(route('fleet.reports.expenses'), false)
            ->assertSee(route('fleet.reports.maintenance'), false)
            ->assertSee(route('fleet.reports.duty-history'), false)
            ->assertSee(route('fleet.create'), false)
            ->assertSee('Add Vehicle')
            ->assertSee($vehicle->registration_no);

        $this->actingAs($user)->get(route('fleet.create'))
            ->assertOk()
            ->assertSee('Vehicle Type')
            ->assertSee('Save Vehicle');

        $this->actingAs($user)->get(route('fleet.reports'))
            ->assertOk()
            ->assertSee('Vehicle Expense Report')
            ->assertSee('Maintenance Report')
            ->assertSee('Staff Duty History Report');

        $this->actingAs($user)->get(route('fleet.show', $vehicle))
            ->assertOk()
            ->assertSee('Assign Driver / Helper / Supervisor');
    }

    public function test_reassigning_a_role_closes_previous_assignment_and_preserves_history(): void
    {
        [$user, $vehicle] = $this->fixture();
        $firstDriver = Employee::create(['name' => 'First Driver', 'status' => 'active']);
        $secondDriver = Employee::create(['name' => 'Second Driver', 'status' => 'active']);

        $this->actingAs($user)->post(route('fleet.assignments.store', $vehicle), [
            'employee_id' => $firstDriver->id, 'duty_role' => 'driver', 'start_date' => '2026-07-01',
        ])->assertRedirect();
        $this->actingAs($user)->post(route('fleet.assignments.store', $vehicle), [
            'employee_id' => $secondDriver->id, 'duty_role' => 'driver', 'start_date' => '2026-07-10',
        ])->assertRedirect();

        $this->assertDatabaseCount('vehicle_assignments_history', 2);
        $firstAssignment = VehicleAssignmentHistory::where('employee_id', $firstDriver->id)->firstOrFail();
        $secondAssignment = VehicleAssignmentHistory::where('employee_id', $secondDriver->id)->firstOrFail();
        $this->assertSame('2026-07-01', $firstAssignment->start_date->format('Y-m-d'));
        $this->assertSame('2026-07-09', $firstAssignment->end_date->format('Y-m-d'));
        $this->assertSame('2026-07-10', $secondAssignment->start_date->format('Y-m-d'));
        $this->assertNull($secondAssignment->end_date);
        $this->assertSame('driver', $secondDriver->refresh()->fleet_role);

        $this->actingAs($user)->get(route('fleet.reports.duty-history', ['from' => '2026-07-05', 'to' => '2026-07-12']))
            ->assertOk()->assertSee('First Driver')->assertSee('Second Driver');
    }

    public function test_maintenance_log_updates_date_and_mileage_schedule(): void
    {
        [$user, $vehicle] = $this->fixture();
        $item = VehicleMaintenanceItem::create([
            'vehicle_id' => $vehicle->id, 'name' => 'Engine Oil', 'maintenance_type' => 'replacement',
            'interval_days' => 90, 'interval_mileage' => 5000,
        ]);

        $this->actingAs($user)->post(route('fleet.maintenance-logs.store', $vehicle), [
            'maintenance_item_id' => $item->id, 'action' => 'changed', 'service_date' => '2026-07-15',
            'mileage' => 12500, 'cost' => 3500, 'vendor' => 'Workshop A',
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame('2026-07-15', $item->last_changed_at->format('Y-m-d'));
        $this->assertSame('2026-10-13', $item->next_due_date->format('Y-m-d'));
        $this->assertSame(17500, $item->next_due_mileage);
        $this->assertSame(12500, $vehicle->refresh()->current_mileage);
        $this->assertDatabaseHas('vehicle_maintenance_logs', ['maintenance_item_id' => $item->id, 'cost' => '3500.00']);
        $this->actingAs($user)->get(route('fleet.reports.maintenance', ['from' => '2026-07-01', 'to' => '2026-07-31']))
            ->assertOk()->assertSee('Workshop A')->assertSee('3,500.00');
    }

    public function test_itemized_expense_is_linked_and_reported_with_date_filter(): void
    {
        [$user, $vehicle] = $this->fixture();
        $driver = Employee::create(['name' => 'Expense Driver', 'fleet_role' => 'driver', 'status' => 'active']);

        $this->actingAs($user)->post(route('fleet.expenses.store', $vehicle), [
            'employee_id' => $driver->id, 'category' => 'fuel_diesel', 'expense_date' => '2026-07-15',
            'amount' => 4200, 'quantity' => 40, 'unit' => 'Liter', 'mileage' => 11000, 'trip_reference' => 'TRIP-101',
        ])->assertRedirect();

        $this->assertDatabaseHas('vehicle_expenses', ['vehicle_id' => $vehicle->id, 'employee_id' => $driver->id, 'created_by' => $user->id, 'category' => 'fuel_diesel', 'amount' => '4200.00']);
        $this->actingAs($user)->get(route('fleet.reports.expenses', ['from' => '2026-07-01', 'to' => '2026-07-31']))
            ->assertOk()->assertSee('TRIP-101')->assertSee('4,200.00')->assertSee('Expense Driver');
        $this->actingAs($user)->get(route('fleet.reports.expenses', ['from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertOk()->assertDontSee('TRIP-101');
    }

    private function fixture(): array
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_fleet')->firstOrFail());
        $vehicle = Vehicle::create([
            'registration_no' => 'DHAKA-METRO-11-1234', 'name' => 'Field Pickup', 'vehicle_type' => 'Pickup',
            'fuel_type' => 'diesel', 'status' => 'active', 'current_mileage' => 10000,
        ]);

        return [$user, $vehicle];
    }
}
