<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleAssignmentHistory;
use App\Models\VehicleExpense;
use App\Models\VehicleMaintenanceItem;
use App\Models\VehicleMaintenanceLog;
use App\Observers\RecordVersionObserver;
use App\Services\FleetMaintenanceMediaService;
use App\Services\FleetService;
use App\Services\RecordVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

class FleetOperationController extends Controller
{
    public function showMaintenanceLog(VehicleMaintenanceLog $maintenanceLog)
    {
        $maintenanceLog->load(['vehicle', 'item', 'creator', 'finalizer', 'photos']);
        $versions = $maintenanceLog->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('fleet.maintenance.show', compact('maintenanceLog', 'versions'));
    }

    public function showExpense(VehicleExpense $expense)
    {
        $expense->load(['vehicle', 'employee', 'creator', 'finalizer']);
        $versions = $expense->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('fleet.expenses.show', compact('expense', 'versions'));
    }

    public function editMaintenanceLog(VehicleMaintenanceLog $maintenanceLog, FleetMaintenanceMediaService $mediaService)
    {
        if ($maintenanceLog->isFinalized()) {
            return redirect()->route('fleet.maintenance-logs.show', $maintenanceLog)->withErrors(['maintenance' => 'Finalized maintenance records cannot be edited.']);
        }

        $maintenanceLog->load(['vehicle', 'photos']);

        return view('fleet.maintenance.edit', [
            'maintenanceLog' => $maintenanceLog,
            'maintenanceItems' => $maintenanceLog->vehicle->maintenanceItems()->where('is_active', true)->orderBy('name')->get(),
            'imageMaxMb' => $mediaService->imageMaxMb(),
        ]);
    }

    public function updateMaintenanceLog(Request $request, VehicleMaintenanceLog $maintenanceLog, FleetService $fleetService, FleetMaintenanceMediaService $mediaService, RecordVersionService $recordVersionService)
    {
        if ($maintenanceLog->isFinalized()) {
            return redirect()->route('fleet.maintenance-logs.show', $maintenanceLog)->withErrors(['maintenance' => 'Finalized maintenance records cannot be edited.']);
        }

        $data = $request->validate([
            'maintenance_item_id' => ['nullable', Rule::exists('vehicle_maintenance_items', 'id')->where('vehicle_id', $maintenanceLog->vehicle_id)],
            'work_name' => ['required_without:maintenance_item_id', 'nullable', 'string', 'max:255'],
            'action' => ['required', Rule::in(array_keys(VehicleMaintenanceLog::ACTIONS))],
            'service_date' => ['required', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:3000'],
            'youtube_url' => ['nullable', 'string', 'max:2048', $mediaService->youtubeUrlRule()],
            'remove_photo_ids' => ['nullable', 'array'],
            'remove_photo_ids.*' => ['integer', Rule::exists('vehicle_maintenance_photos', 'id')->where('vehicle_maintenance_log_id', $maintenanceLog->id)],
            ...$mediaService->imageRules(),
        ]);
        $newPhotos = $data['photos'] ?? [];
        $removePhotoIds = $data['remove_photo_ids'] ?? [];
        unset($data['photos'], $data['remove_photo_ids']);
        $storedPaths = [];
        $removedPaths = [];
        $becameFinalized = false;

        try {
            DB::transaction(function () use (&$maintenanceLog, &$storedPaths, &$removedPaths, &$becameFinalized, $data, $newPhotos, $removePhotoIds, $fleetService, $mediaService, $recordVersionService): void {
                $maintenanceLog = VehicleMaintenanceLog::query()->with(['vehicle', 'item', 'creator', 'photos'])->whereKey($maintenanceLog->id)->lockForUpdate()->firstOrFail();
                if ($maintenanceLog->isFinalized()) {
                    $becameFinalized = true;

                    return;
                }

                $oldSnapshot = $recordVersionService->snapshot($maintenanceLog, ['vehicle', 'item', 'creator', 'photos']);
                $item = filled($data['maintenance_item_id'] ?? null) ? VehicleMaintenanceItem::findOrFail($data['maintenance_item_id']) : null;
                RecordVersionObserver::withoutRecording(fn () => $fleetService->updateMaintenance($maintenanceLog, $item, $data));

                $photosToRemove = $maintenanceLog->photos()->whereIn('id', $removePhotoIds)->get();
                $removedPaths = $photosToRemove->pluck('path')->all();
                $maintenanceLog->photos()->whereIn('id', $removePhotoIds)->delete();
                $storedPaths = $mediaService->attachPhotos($maintenanceLog, $newPhotos);

                $maintenanceLog->unsetRelations()->refresh();
                $newSnapshot = $recordVersionService->snapshot($maintenanceLog, ['vehicle', 'item', 'creator', 'photos']);
                $recordVersionService->recordUpdate($maintenanceLog, $oldSnapshot, $newSnapshot, ['source' => 'fleet_maintenance_edit']);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        if ($becameFinalized) {
            Storage::disk('local')->delete($storedPaths);

            return redirect()->route('fleet.maintenance-logs.show', $maintenanceLog)->withErrors(['maintenance' => 'This record was finalized before your edit could be saved.']);
        }

        Storage::disk('local')->delete($removedPaths);

        return redirect()->route('fleet.maintenance-logs.show', $maintenanceLog)->with('success', 'Draft maintenance record updated and the old version was saved.');
    }

    public function finalizeMaintenanceLog(VehicleMaintenanceLog $maintenanceLog, RecordVersionService $recordVersionService)
    {
        DB::transaction(function () use (&$maintenanceLog, $recordVersionService): void {
            $maintenanceLog = VehicleMaintenanceLog::query()->with(['vehicle', 'item', 'creator', 'photos'])->whereKey($maintenanceLog->id)->lockForUpdate()->firstOrFail();
            if ($maintenanceLog->isFinalized()) {
                return;
            }

            $oldSnapshot = $recordVersionService->snapshot($maintenanceLog, ['vehicle', 'item', 'creator', 'photos']);
            RecordVersionObserver::withoutRecording(fn () => $maintenanceLog->update(['finalized_at' => now(), 'finalized_by' => auth()->id()]));
            $maintenanceLog->unsetRelations()->refresh();
            $newSnapshot = $recordVersionService->snapshot($maintenanceLog, ['vehicle', 'item', 'creator', 'finalizer', 'photos']);
            $recordVersionService->recordUpdate($maintenanceLog, $oldSnapshot, $newSnapshot, ['source' => 'fleet_maintenance_finalize']);
        });

        return redirect()->route('fleet.maintenance-logs.show', $maintenanceLog)->with('success', 'Maintenance record finalized. Editing is now locked.');
    }

    public function editExpense(VehicleExpense $expense)
    {
        if ($expense->isFinalized()) {
            return redirect()->route('fleet.expenses.show', $expense)->withErrors(['expense' => 'Finalized expense records cannot be edited.']);
        }

        $expense->load('vehicle');

        return view('fleet.expenses.edit', [
            'expense' => $expense,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function updateExpense(Request $request, VehicleExpense $expense, RecordVersionService $recordVersionService)
    {
        if ($expense->isFinalized()) {
            return redirect()->route('fleet.expenses.show', $expense)->withErrors(['expense' => 'Finalized expense records cannot be edited.']);
        }

        $data = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'category' => ['required', Rule::in(array_keys(VehicleExpense::CATEGORIES))],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'unit' => ['nullable', 'string', 'max:30'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'trip_reference' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);
        $becameFinalized = false;

        DB::transaction(function () use (&$expense, &$becameFinalized, $data, $recordVersionService): void {
            $expense = VehicleExpense::query()->with(['vehicle', 'employee', 'creator'])->whereKey($expense->id)->lockForUpdate()->firstOrFail();
            if ($expense->isFinalized()) {
                $becameFinalized = true;

                return;
            }

            $oldSnapshot = $recordVersionService->snapshot($expense, ['vehicle', 'employee', 'creator']);
            RecordVersionObserver::withoutRecording(fn () => $expense->update($data));
            if (isset($data['mileage']) && $data['mileage'] > $expense->vehicle->current_mileage) {
                $expense->vehicle->update(['current_mileage' => $data['mileage']]);
            }
            $expense->unsetRelations()->refresh();
            $newSnapshot = $recordVersionService->snapshot($expense, ['vehicle', 'employee', 'creator']);
            $recordVersionService->recordUpdate($expense, $oldSnapshot, $newSnapshot, ['source' => 'fleet_expense_edit']);
        });

        if ($becameFinalized) {
            return redirect()->route('fleet.expenses.show', $expense)->withErrors(['expense' => 'This record was finalized before your edit could be saved.']);
        }

        return redirect()->route('fleet.expenses.show', $expense)->with('success', 'Draft expense updated and the old version was saved.');
    }

    public function finalizeExpense(VehicleExpense $expense, RecordVersionService $recordVersionService)
    {
        DB::transaction(function () use (&$expense, $recordVersionService): void {
            $expense = VehicleExpense::query()->with(['vehicle', 'employee', 'creator'])->whereKey($expense->id)->lockForUpdate()->firstOrFail();
            if ($expense->isFinalized()) {
                return;
            }

            $oldSnapshot = $recordVersionService->snapshot($expense, ['vehicle', 'employee', 'creator']);
            RecordVersionObserver::withoutRecording(fn () => $expense->update(['finalized_at' => now(), 'finalized_by' => auth()->id()]));
            $expense->unsetRelations()->refresh();
            $newSnapshot = $recordVersionService->snapshot($expense, ['vehicle', 'employee', 'creator', 'finalizer']);
            $recordVersionService->recordUpdate($expense, $oldSnapshot, $newSnapshot, ['source' => 'fleet_expense_finalize']);
        });

        return redirect()->route('fleet.expenses.show', $expense)->with('success', 'Expense finalized. Editing is now locked.');
    }

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
