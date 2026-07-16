<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceItem;
use App\Services\FleetMaintenanceMediaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FleetController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::query()
            ->with(['activeAssignments.employee'])
            ->withCount(['maintenanceItems as due_maintenance_count' => fn ($query) => $query->where('is_active', true)->where(function ($query): void {
                $query->whereDate('next_due_date', '<=', today())->orWhereColumn('next_due_mileage', '<=', 'vehicles.current_mileage');
            })])
            ->withSum(['expenses as filtered_expense_total' => fn ($query) => $query
                ->when($request->filled('from'), fn ($query) => $query->whereDate('expense_date', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('expense_date', '<=', $request->date('to')))], 'amount')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(fn ($query) => $query->where('registration_no', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('make', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%"));
            })
            ->orderBy('registration_no')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('fleet.index', [
            'vehicles' => $vehicles,
            'vehicleCount' => Vehicle::count(),
            'activeCount' => Vehicle::where('status', 'active')->count(),
            'maintenanceCount' => Vehicle::where('status', 'maintenance')->count(),
            'dueCount' => VehicleMaintenanceItem::query()->where('is_active', true)->where(fn ($query) => $query->whereDate('next_due_date', '<=', today())->orWhereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->whereColumn('vehicle_maintenance_items.next_due_mileage', '<=', 'vehicles.current_mileage')))->count(),
        ]);
    }

    public function store(Request $request)
    {
        Vehicle::create($this->validatedVehicle($request));

        return redirect()->route('fleet.index')->with('success', 'Vehicle added successfully.');
    }

    public function create()
    {
        return view('fleet.create');
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load([
            'maintenanceItems' => fn ($query) => $query->orderByDesc('is_active')->orderBy('name'),
            'maintenanceLogs' => fn ($query) => $query->with(['item', 'creator', 'photos'])->latest('service_date')->latest()->limit(30),
            'assignments' => fn ($query) => $query->with(['employee', 'assigner'])->latest('start_date')->latest()->limit(50),
            'expenses' => fn ($query) => $query->with(['employee', 'creator'])->latest('expense_date')->latest()->limit(30),
        ]);
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('fleet.show', [
            'vehicle' => $vehicle,
            'employees' => $employees,
            'imageMaxMb' => app(FleetMaintenanceMediaService::class)->imageMaxMb(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $vehicle->update($this->validatedVehicle($request, $vehicle));

        return redirect()->route('fleet.show', $vehicle)->with('success', 'Vehicle details updated.');
    }

    private function validatedVehicle(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'registration_no' => ['required', 'string', 'max:100', Rule::unique('vehicles')->ignore($vehicle?->id)],
            'name' => ['required', 'string', 'max:255'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'chassis_no' => ['nullable', 'string', 'max:150', Rule::unique('vehicles')->ignore($vehicle?->id)],
            'engine_no' => ['nullable', 'string', 'max:150', Rule::unique('vehicles')->ignore($vehicle?->id)],
            'fuel_type' => ['nullable', Rule::in(array_keys(Vehicle::FUEL_TYPES))],
            'status' => ['required', Rule::in(array_keys(Vehicle::STATUSES))],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
