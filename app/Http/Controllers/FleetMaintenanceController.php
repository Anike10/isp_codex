<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleMaintenanceItem;
use App\Models\VehicleMaintenanceLog;
use App\Services\FleetService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FleetMaintenanceController extends Controller
{
    public function schedules(Request $request)
    {
        $query = $this->scheduleQuery($request);

        return view('fleet.maintenance.schedules', [
            'vehicles' => Vehicle::orderBy('registration_no')->get(),
            'schedules' => $query->paginate($this->perPage($request))->withQueryString(),
            'statusCounts' => $this->statusCounts(),
        ]);
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('vehicle_maintenance_items', 'name')->where('vehicle_id', $request->integer('vehicle_id'))],
            'maintenance_type' => ['required', Rule::in(array_keys(VehicleMaintenanceItem::TYPES))],
            'interval_days' => ['nullable', 'integer', 'min:1'],
            'interval_mileage' => ['nullable', 'integer', 'min:1'],
            'next_due_date' => ['nullable', 'date'],
            'next_due_mileage' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        VehicleMaintenanceItem::create($data);

        return redirect()->route('fleet.maintenance.schedules', ['vehicle_id' => $data['vehicle_id']])->with('success', 'Periodic maintenance schedule added.');
    }

    public function createLog(Request $request)
    {
        $selectedVehicle = $request->filled('vehicle_id') ? Vehicle::find($request->integer('vehicle_id')) : null;

        return view('fleet.maintenance.log_create', [
            'vehicles' => Vehicle::orderBy('registration_no')->get(),
            'selectedVehicle' => $selectedVehicle,
            'maintenanceItems' => $selectedVehicle?->maintenanceItems()->where('is_active', true)->orderBy('name')->get() ?? collect(),
        ]);
    }

    public function storeLog(Request $request, FleetService $fleetService)
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'maintenance_item_id' => ['nullable', Rule::exists('vehicle_maintenance_items', 'id')->where('vehicle_id', $request->integer('vehicle_id'))],
            'work_name' => ['required_without:maintenance_item_id', 'nullable', 'string', 'max:255'],
            'action' => ['required', Rule::in(array_keys(VehicleMaintenanceLog::ACTIONS))],
            'service_date' => ['required', 'date'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:3000'],
        ]);
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $item = filled($data['maintenance_item_id'] ?? null) ? VehicleMaintenanceItem::findOrFail($data['maintenance_item_id']) : null;
        $fleetService->logMaintenance($vehicle, $item, $data, $request->user()?->id);

        return redirect()->route('fleet.maintenance.logs.create', ['vehicle_id' => $vehicle->id])->with('success', 'Repair / maintenance entry saved and next due schedule recalculated.');
    }

    private function scheduleQuery(Request $request): Builder
    {
        $query = VehicleMaintenanceItem::query()
            ->select('vehicle_maintenance_items.*')
            ->join('vehicles', 'vehicles.id', '=', 'vehicle_maintenance_items.vehicle_id')
            ->with('vehicle')
            ->where('vehicle_maintenance_items.is_active', true)
            ->when($request->filled('vehicle_id'), fn ($query) => $query->where('vehicle_maintenance_items.vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('maintenance_type', $request->query('type')))
            ->when($request->filled('search'), fn ($query) => $query->where('vehicle_maintenance_items.name', 'like', '%'.trim((string) $request->query('search')).'%'));

        $this->applyStatusFilter($query, (string) $request->query('status'));

        return $query->orderByRaw('CASE
            WHEN next_due_date < ? OR next_due_mileage < vehicles.current_mileage THEN 0
            WHEN next_due_date = ? OR next_due_mileage = vehicles.current_mileage THEN 1
            WHEN next_due_date IS NULL AND next_due_mileage IS NULL THEN 3
            ELSE 2 END', [today()->toDateString(), today()->toDateString()])
            ->orderBy('next_due_date')->orderBy('next_due_mileage')->orderBy('vehicles.registration_no');
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'overdue') {
            $query->where(fn ($query) => $query->whereDate('next_due_date', '<', today())->orWhereColumn('next_due_mileage', '<', 'vehicles.current_mileage'));
        } elseif ($status === 'due') {
            $query->where(fn ($query) => $query->whereDate('next_due_date', today())->orWhereColumn('next_due_mileage', 'vehicles.current_mileage'))
                ->where(fn ($query) => $query->whereNull('next_due_date')->orWhereDate('next_due_date', '>=', today()))
                ->where(fn ($query) => $query->whereNull('next_due_mileage')->orWhereColumn('next_due_mileage', '>=', 'vehicles.current_mileage'));
        } elseif ($status === 'upcoming') {
            $query->where(fn ($query) => $query->whereNotNull('next_due_date')->orWhereNotNull('next_due_mileage'))
                ->where(fn ($query) => $query->whereNull('next_due_date')->orWhereDate('next_due_date', '>', today()))
                ->where(fn ($query) => $query->whereNull('next_due_mileage')->orWhereColumn('next_due_mileage', '>', 'vehicles.current_mileage'));
        } elseif ($status === 'unscheduled') {
            $query->whereNull('next_due_date')->whereNull('next_due_mileage');
        }
    }

    private function statusCounts(): array
    {
        return VehicleMaintenanceItem::query()->with('vehicle')->where('is_active', true)->get()
            ->countBy(fn (VehicleMaintenanceItem $item) => $item->dueStatus($item->vehicle->current_mileage))
            ->all();
    }
}
