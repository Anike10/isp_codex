@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Fleet Reports</h1><div class="muted">Vehicle expenses, maintenance activity, and complete staff duty history</div></div><a class="btn light" href="{{ route('fleet.index') }}">Vehicles</a></div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Vehicle</label><select name="vehicle_id"><option value="">All</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((int)request('vehicle_id')===$vehicle->id)>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</option>@endforeach</select></div>
    <div><label>Employee</label><select name="employee_id"><option value="">All</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int)request('employee_id')===$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
    <div><label>Expense Category</label><select name="category"><option value="">All</option>@foreach(\App\Models\VehicleExpense::CATEGORIES as $key=>$label)<option value="{{ $key }}" @selected(request('category')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Duty Role</label><select name="duty_role"><option value="">All</option>@foreach(\App\Models\VehicleAssignmentHistory::ROLES as $key=>$label)<option value="{{ $key }}" @selected(request('duty_role')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="full actions"><button class="btn">Apply Filters</button><a class="btn light" href="{{ route('fleet.reports') }}">Reset</a></div>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Vehicle Expense</span><strong>{{ number_format((float)$expenseTotal,2) }}</strong></div>
    <div class="card stat"><span class="muted">Maintenance Cost</span><strong>{{ number_format((float)$maintenanceTotal,2) }}</strong></div>
    <div class="card stat"><span class="muted">Expense Entries</span><strong>{{ $expenseCount }}</strong></div>
    <div class="card stat"><span class="muted">Duty Records</span><strong>{{ $dutyCount }}</strong></div>
</div>

<section class="card" style="margin-bottom:16px"><h2>Vehicle-wise Total Expenses</h2><table><thead><tr><th>Vehicle</th><th>Entries</th><th>Total</th></tr></thead><tbody>
@forelse($expenseByVehicle as $row)<tr><td><a href="{{ route('fleet.show',$row->vehicle) }}">{{ $row->vehicle->registration_no }} — {{ $row->vehicle->name }}</a></td><td>{{ $row->entry_count }}</td><td><strong>{{ number_format((float)$row->total_amount,2) }}</strong></td></tr>@empty<tr><td colspan="3">No expense data for the selected range.</td></tr>@endforelse</tbody></table></section>

<section class="card" style="margin-bottom:16px"><h2>Itemized Vehicle Expenses</h2><table><thead><tr><th>Date</th><th>Vehicle</th><th>Category</th><th>Driver</th><th>Trip</th><th>Amount</th><th>Entered By</th></tr></thead><tbody>
@forelse($expenseRows as $row)<tr><td>{{ $row->expense_date->format('Y-m-d') }}</td><td>{{ $row->vehicle->registration_no }}</td><td>{{ \App\Models\VehicleExpense::CATEGORIES[$row->category] }}</td><td>{{ $row->employee?->name ?? 'N/A' }}</td><td>{{ $row->trip_reference ?? 'N/A' }}</td><td>{{ number_format((float)$row->amount,2) }}</td><td>{{ $row->creator?->name ?? 'N/A' }}</td></tr>@empty<tr><td colspan="7">No expenses found.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $expenseRows->links() }}</div></section>

<section class="card" style="margin-bottom:16px"><h2>Maintenance Logs</h2><table><thead><tr><th>Date</th><th>Vehicle</th><th>Item</th><th>Action</th><th>Mileage</th><th>Cost</th><th>Details</th></tr></thead><tbody>
@forelse($maintenanceLogs as $log)<tr><td>{{ $log->service_date->format('Y-m-d') }}</td><td>{{ $log->vehicle->registration_no }}</td><td>{{ $log->item?->name ?? 'General' }}</td><td>{{ ucfirst($log->action) }}</td><td>{{ $log->mileage ? number_format($log->mileage) : 'N/A' }}</td><td>{{ number_format((float)$log->cost,2) }}</td><td>{{ $log->details ?? 'N/A' }}</td></tr>@empty<tr><td colspan="7">No maintenance logs found.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $maintenanceLogs->links() }}</div></section>

<section class="card"><h2>Staff Duty History</h2><table><thead><tr><th>Vehicle</th><th>Role</th><th>Employee</th><th>Start</th><th>End</th><th>Note</th></tr></thead><tbody>
@forelse($assignments as $assignment)<tr><td>{{ $assignment->vehicle->registration_no }}</td><td>{{ ucfirst($assignment->duty_role) }}</td><td>{{ $assignment->employee->name }}</td><td>{{ $assignment->start_date->format('Y-m-d') }}</td><td>{{ $assignment->end_date?->format('Y-m-d') ?? 'Current' }}</td><td>{{ $assignment->note ?? 'N/A' }}</td></tr>@empty<tr><td colspan="6">No duty history found.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $assignments->links() }}</div></section>
@endsection
