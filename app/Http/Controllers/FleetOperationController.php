<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleAssignmentHistory;
use App\Models\VehicleExpense;
use App\Models\VehicleMaintenanceItem;
use App\Models\VehicleMaintenanceLog;
use App\Services\FleetMaintenanceMediaService;
use App\Services\FleetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class FleetOperationController extends Controller
{
    public function storeMaintenanceItem(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('vehicle_maintenance_items', 'name')->where('vehicle_id', $vehicle->id)], 'maintenance_type' => ['required', Rule::in(array_keys(VehicleMaintenanceItem::TYPES))],
            'interval_days' => ['nullable', 'integer', 'min:1'], 'interval_mileage' => ['nullable', 'integer', 'min:1'],
            'next_due_date' => ['nullable', 'date'], 'next_due_mileage' => ['nullable', 'integer', 'min:0'], 'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $vehicle->maintenanceItems()->create($data);

        return back()->with('success', 'Maintenance schedule added.');
    }

    public function storeMaintenanceLog(Request $request, Vehicle $vehicle, FleetService $fleetService, FleetMaintenanceMediaService $mediaService)
    {
        $data = $request->validate([
            'maintenance_item_id' => ['nullable', Rule::exists('vehicle_maintenance_items', 'id')->where('vehicle_id', $vehicle->id)],
            'work_name' => ['required_without:maintenance_item_id', 'nullable', 'string', 'max:255'],
            'action' => ['required', Rule::in(array_keys(VehicleMaintenanceLog::ACTIONS))], 'service_date' => ['required', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'], 'cost' => ['required', 'numeric', 'min:0'], 'vendor' => ['nullable', 'string', 'max:255'], 'details' => ['nullable', 'string', 'max:3000'],
            'youtube_url' => ['nullable', 'string', 'max:2048', $mediaService->youtubeUrlRule()],
            ...$mediaService->imageRules(),
        ]);
        $item = filled($data['maintenance_item_id'] ?? null) ? VehicleMaintenanceItem::findOrFail($data['maintenance_item_id']) : null;
        $photos = $data['photos'] ?? [];
        unset($data['photos']);
        DB::transaction(function () use ($fleetService, $mediaService, $vehicle, $item, $data, $photos, $request): void {
            $log = $fleetService->logMaintenance($vehicle, $item, $data, $request->user()?->id);
            $mediaService->attachPhotos($log, $photos);
        });

        return back()->with('success', 'Maintenance log saved and next due schedule updated.');
    }

    public function storeAssignment(Request $request, Vehicle $vehicle, FleetService $fleetService)
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('status', 'active')],
            'duty_role' => ['required', Rule::in(array_keys(VehicleAssignmentHistory::ROLES))], 'start_date' => ['required', 'date'], 'note' => ['nullable', 'string', 'max:2000'],
        ]);
        try {
            $fleetService->assignEmployee($vehicle, Employee::findOrFail($data['employee_id']), $data, $request->user()?->id);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['employee_id' => $exception->getMessage()]);
        }

        return back()->with('success', 'Employee assigned and previous duty history closed.');
    }

    public function endAssignment(Request $request, VehicleAssignmentHistory $assignment, FleetService $fleetService)
    {
        $data = $request->validate(['end_date' => ['required', 'date']]);
        try {
            $fleetService->endAssignment($assignment, $data['end_date']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['end_date' => $exception->getMessage()]);
        }

        return back()->with('success', 'Duty assignment ended.');
    }

    public function storeExpense(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'], 'category' => ['required', Rule::in(array_keys(VehicleExpense::CATEGORIES))],
            'expense_date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'min:0.01'], 'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'unit' => ['nullable', 'string', 'max:30'], 'mileage' => ['nullable', 'integer', 'min:0'], 'trip_reference' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:3000'],
        ]);
        $vehicle->expenses()->create([...$data, 'created_by' => $request->user()?->id]);
        if (isset($data['mileage']) && $data['mileage'] > $vehicle->current_mileage) {
            $vehicle->update(['current_mileage' => $data['mileage']]);
        }

        return back()->with('success', 'Vehicle expense recorded.');
    }
}
