<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleAssignmentHistory;
use App\Models\VehicleExpense;
use App\Models\VehicleMaintenanceItem;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Http\Request;

class FleetReportController extends Controller
{
    public function index()
    {
        return view('fleet.reports.index', [
            'expenseCount' => VehicleExpense::count(),
            'maintenanceCount' => VehicleMaintenanceLog::count(),
            'dutyCount' => VehicleAssignmentHistory::count(),
            'dueCount' => VehicleMaintenanceItem::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query
                    ->whereDate('next_due_date', '<=', today())
                    ->orWhereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery
                        ->whereColumn('vehicle_maintenance_items.next_due_mileage', '<=', 'vehicles.current_mileage')))
                ->count(),
        ]);
    }

    public function expenses(Request $request)
    {
        $query = VehicleExpense::query()
            ->when($request->filled('vehicle_id'), fn ($query) => $query->where('vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('expense_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('expense_date', '<=', $request->date('to')));

        return view('fleet.reports.expenses', [
            ...$this->filterOptions(),
            'expenseTotal' => (clone $query)->sum('amount'),
            'expenseCount' => (clone $query)->count(),
            'expenseByVehicle' => (clone $query)
                ->selectRaw('vehicle_id, COUNT(*) as entry_count, SUM(amount) as total_amount')
                ->with('vehicle')->groupBy('vehicle_id')->orderByDesc('total_amount')->get(),
            'expenseRows' => (clone $query)->with(['vehicle', 'employee', 'creator'])
                ->latest('expense_date')->latest()->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function maintenance(Request $request)
    {
        $query = VehicleMaintenanceLog::query()
            ->when($request->filled('vehicle_id'), fn ($query) => $query->where('vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->query('action')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('service_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('service_date', '<=', $request->date('to')));

        return view('fleet.reports.maintenance', [
            ...$this->filterOptions(),
            'maintenanceTotal' => (clone $query)->sum('cost'),
            'maintenanceCount' => (clone $query)->count(),
            'maintenanceLogs' => (clone $query)->with(['vehicle', 'item', 'creator'])
                ->latest('service_date')->latest()->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function dutyHistory(Request $request)
    {
        $query = VehicleAssignmentHistory::query()
            ->when($request->filled('vehicle_id'), fn ($query) => $query->where('vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('duty_role'), fn ($query) => $query->where('duty_role', $request->query('duty_role')))
            ->when($request->query('status') === 'current', fn ($query) => $query->whereNull('end_date'))
            ->when($request->query('status') === 'ended', fn ($query) => $query->whereNotNull('end_date'))
            ->when($request->filled('from'), fn ($query) => $query->where(fn ($query) => $query
                ->whereNull('end_date')->orWhereDate('end_date', '>=', $request->date('from'))))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('start_date', '<=', $request->date('to')));

        return view('fleet.reports.duty_history', [
            ...$this->filterOptions(),
            'dutyCount' => (clone $query)->count(),
            'currentCount' => (clone $query)->whereNull('end_date')->count(),
            'assignments' => (clone $query)->with(['vehicle', 'employee', 'assigner'])
                ->latest('start_date')->latest()->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    private function filterOptions(): array
    {
        return [
            'vehicles' => Vehicle::orderBy('registration_no')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ];
    }
}
