<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignmentHistory;
use App\Models\VehicleMaintenanceItem;
use App\Models\VehicleMaintenanceLog;
use App\Services\FleetMaintenanceMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee(route('fleet.maintenance.schedules'), false)
            ->assertSee(route('fleet.maintenance.logs.create'), false)
            ->assertSee(route('fleet.reports.maintenance-due'), false)
            ->assertSee(route('fleet.create'), false)
            ->assertSee('Logged in user')
            ->assertSee($user->name)
            ->assertSee('Add Vehicle')
            ->assertSeeInOrder([route('fleet.index'), route('fleet.create'), route('fleet.reports')], false)
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

    public function test_employee_cannot_remain_active_on_two_vehicles_under_different_roles(): void
    {
        [$user, $firstVehicle] = $this->fixture();
        $secondVehicle = Vehicle::create([
            'registration_no' => 'DHAKA-METRO-22-5678', 'name' => 'Service Van',
            'vehicle_type' => 'Van', 'status' => 'active', 'current_mileage' => 5000,
        ]);
        $employee = Employee::create(['name' => 'Moving Staff', 'status' => 'active']);

        $this->actingAs($user)->post(route('fleet.assignments.store', $firstVehicle), [
            'employee_id' => $employee->id, 'duty_role' => 'helper', 'start_date' => '2026-07-01',
        ])->assertRedirect();
        $this->actingAs($user)->post(route('fleet.assignments.store', $secondVehicle), [
            'employee_id' => $employee->id, 'duty_role' => 'driver', 'start_date' => '2026-07-10',
        ])->assertRedirect();

        $endedAssignment = VehicleAssignmentHistory::where('vehicle_id', $firstVehicle->id)
            ->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('helper', $endedAssignment->duty_role);
        $this->assertSame('2026-07-09', $endedAssignment->end_date->format('Y-m-d'));
        $this->assertDatabaseHas('vehicle_assignments_history', [
            'vehicle_id' => $secondVehicle->id, 'employee_id' => $employee->id,
            'duty_role' => 'driver', 'end_date' => null,
        ]);
        $this->assertSame(1, VehicleAssignmentHistory::where('employee_id', $employee->id)->whereNull('end_date')->count());
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

    public function test_periodic_schedule_entry_and_due_overdue_reports_work(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 10:00:00'));
        [$user, $vehicle] = $this->fixture();

        $this->actingAs($user)->post(route('fleet.maintenance.schedules.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Brake Check',
            'maintenance_type' => 'routine_check',
            'interval_days' => 30,
            'interval_mileage' => 2000,
            'next_due_date' => '2026-07-10',
            'next_due_mileage' => 9500,
        ])->assertRedirect(route('fleet.maintenance.schedules', ['vehicle_id' => $vehicle->id]));
        $this->actingAs($user)->post(route('fleet.maintenance.schedules.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Air Filter',
            'maintenance_type' => 'replacement',
            'interval_days' => 90,
            'next_due_date' => '2026-07-20',
        ])->assertRedirect();

        $brakeCheck = VehicleMaintenanceItem::where('name', 'Brake Check')->firstOrFail();
        $this->actingAs($user)->get(route('fleet.maintenance.schedules'))
            ->assertOk()->assertSee('5 days overdue')->assertSee('500 km overdue')->assertSee('5 days left');
        $this->actingAs($user)->get(route('fleet.reports.maintenance-due', ['status' => 'overdue']))
            ->assertOk()->assertSee('Brake Check')->assertDontSee('Air Filter');

        $this->actingAs($user)->get(route('fleet.maintenance.logs.create', ['vehicle_id' => $vehicle->id, 'maintenance_item_id' => $brakeCheck->id]))
            ->assertOk()->assertSee('Brake Check')->assertSee('Save Work / Maintenance', false);
        $this->actingAs($user)->post(route('fleet.maintenance.logs.store'), [
            'vehicle_id' => $vehicle->id,
            'maintenance_item_id' => $brakeCheck->id,
            'action' => 'serviced',
            'service_date' => '2026-07-15',
            'mileage' => 10100,
            'cost' => 500,
            'vendor' => 'Central Workshop',
            'details' => 'Brake cleaned and adjusted.',
        ])->assertRedirect(route('fleet.maintenance.logs.create', ['vehicle_id' => $vehicle->id]));

        $this->assertSame('2026-08-14', $brakeCheck->refresh()->next_due_date->format('Y-m-d'));
        $this->assertSame(12100, $brakeCheck->next_due_mileage);
        $this->actingAs($user)->get(route('fleet.reports.maintenance'))
            ->assertOk()->assertSee('Central Workshop')->assertSee('Brake cleaned and adjusted.');
    }

    public function test_unscheduled_repair_can_be_logged_without_a_periodic_schedule(): void
    {
        [$user, $vehicle] = $this->fixture();

        $this->actingAs($user)->post(route('fleet.maintenance.logs.store'), [
            'vehicle_id' => $vehicle->id,
            'work_name' => 'Clutch plate repair',
            'action' => 'repaired',
            'service_date' => '2026-07-15',
            'mileage' => 10500,
            'cost' => 6500,
            'vendor' => 'General Workshop',
            'details' => 'Clutch plate and release bearing replaced.',
        ])->assertRedirect(route('fleet.maintenance.logs.create', ['vehicle_id' => $vehicle->id]));

        $this->assertDatabaseHas('vehicle_maintenance_logs', [
            'vehicle_id' => $vehicle->id, 'maintenance_item_id' => null,
            'work_name' => 'Clutch plate repair', 'action' => 'repaired', 'cost' => '6500.00',
        ]);
        $this->actingAs($user)->get(route('fleet.reports.maintenance'))
            ->assertOk()->assertSee('Clutch plate repair')->assertSee('6,500.00');
    }

    public function test_maintenance_work_accepts_private_photos_and_a_youtube_link(): void
    {
        Storage::fake('local');
        [$user, $vehicle] = $this->fixture();

        $this->actingAs($user)->post(route('fleet.maintenance.logs.store'), [
            'vehicle_id' => $vehicle->id,
            'work_name' => 'Gearbox repair',
            'action' => 'repaired',
            'service_date' => '2026-07-16',
            'cost' => 8500,
            'youtube_url' => 'https://youtu.be/abc123xyz',
            'photos' => [
                $this->fakePng('parts-receipt.png', 500),
                $this->fakePng('work-complete.png', 400),
            ],
        ])->assertRedirect(route('fleet.maintenance.logs.create', ['vehicle_id' => $vehicle->id]));

        $log = VehicleMaintenanceLog::with('photos')->firstOrFail();
        $this->assertSame('https://youtu.be/abc123xyz', $log->youtube_url);
        $this->assertCount(2, $log->photos);
        foreach ($log->photos as $photo) {
            Storage::disk('local')->assertExists($photo->path);
        }

        $this->actingAs($user)->get(route('fleet.maintenance.photos.show', $log->photos->first()))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('fleet.maintenance.photos.show', $log->photos->first()))->assertForbidden();
        $this->actingAs($user)->get(route('fleet.reports.maintenance'))
            ->assertOk()->assertSee('Photo 1')->assertSee('Photo 2')->assertSee('YouTube Video');
    }

    public function test_fleet_settings_control_each_image_maximum_size_and_youtube_must_be_valid(): void
    {
        Storage::fake('local');
        [$user, $vehicle] = $this->fixture();

        $this->actingAs($user)->get(route('fleet.settings'))
            ->assertOk()->assertSee('Maximum Size Per Image');
        $this->actingAs($user)->post(route('fleet.settings.update'), ['image_max_mb' => 1])->assertRedirect();
        $this->assertSame('1', AppSetting::value(FleetMaintenanceMediaService::IMAGE_MAX_MB_SETTING));

        $payload = [
            'vehicle_id' => $vehicle->id,
            'work_name' => 'Body repair',
            'action' => 'repaired',
            'service_date' => '2026-07-16',
            'cost' => 1000,
        ];

        $this->actingAs($user)->post(route('fleet.maintenance.logs.store'), [
            ...$payload,
            'photos' => [$this->fakePng('too-large.png', 1100)],
        ])->assertSessionHasErrors('photos.0');

        $this->actingAs($user)->post(route('fleet.maintenance.logs.store'), [
            ...$payload,
            'youtube_url' => 'https://example.com/not-youtube',
        ])->assertSessionHasErrors('youtube_url');

        $this->assertDatabaseCount('vehicle_maintenance_logs', 0);
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

    private function fakePng(string $name, int $kilobytes): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $png.str_repeat("\0", max(0, ($kilobytes * 1024) - strlen($png))));
    }
}
