<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleAssignmentHistory;
use App\Models\VehicleExpense;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Http\Request;

class FleetReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from');
        $to = $request->date('to');
        $vehicleId = $request->integer('vehicle_id') ?: null;
        $employeeId = $request->integer('employee_id') ?: null;

        $expenseBase = VehicleExpense::query()
            ->when($vehicleId, fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($from, fn ($query) => $query->whereDate('expense_date', '>=', $from))->when($to, fn ($query) => $query->whereDate('expense_date', '<=', $to));
        $expenseTotal = (clone $expenseBase)->sum('amount');
        $expenseCount = (clone $expenseBase)->count();
        $expenseByVehicle = (clone $expenseBase)->selectRaw('vehicle_id, COUNT(*) as entry_count, SUM(amount) as total_amount')->with('vehicle')->groupBy('vehicle_id')->orderByDesc('total_amount')->get();
        $expenseRows = (clone $expenseBase)->with(['vehicle', 'employee', 'creator'])->latest('expense_date')->latest()->paginate(25, ['*'], 'expense_page')->withQueryString();

        $maintenanceBase = VehicleMaintenanceLog::query()
            ->when($vehicleId, fn ($query) => $query->where('vehicle_id', $vehicleId))
            ->when($from, fn ($query) => $query->whereDate('service_date', '>=', $from))->when($to, fn ($query) => $query->whereDate('service_date', '<=', $to));
        $maintenanceTotal = (clone $maintenanceBase)->sum('cost');
        $maintenanceLogs = (clone $maintenanceBase)->with(['vehicle', 'item', 'creator'])->latest('service_date')->latest()->paginate(25, ['*'], 'maintenance_page')->withQueryString();

        $assignmentBase = VehicleAssignmentHistory::query()
            ->when($vehicleId, fn ($query) => $query->where('vehicle_id', $vehicleId))->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($request->filled('duty_role'), fn ($query) => $query->where('duty_role', $request->query('duty_role')))
            ->when($from, fn ($query) => $query->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $from)))
            ->when($to, fn ($query) => $query->whereDate('start_date', '<=', $to));
        $dutyCount = (clone $assignmentBase)->count();
        $assignments = (clone $assignmentBase)->with(['vehicle', 'employee', 'assigner'])->latest('start_date')->latest()->paginate(25, ['*'], 'duty_page')->withQueryString();

        return view('fleet.reports', [
            'vehicles' => Vehicle::orderBy('registration_no')->get(), 'employees' => Employee::orderBy('name')->get(),
            'expenseRows' => $expenseRows, 'expenseByVehicle' => $expenseByVehicle, 'expenseTotal' => $expenseTotal, 'expenseCount' => $expenseCount,
            'maintenanceLogs' => $maintenanceLogs, 'maintenanceTotal' => $maintenanceTotal, 'assignments' => $assignments, 'dutyCount' => $dutyCount,
        ]);
    }
}
