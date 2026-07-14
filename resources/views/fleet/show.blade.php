@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</h1><div class="muted">{{ $vehicle->vehicle_type ?? 'Vehicle' }} | {{ number_format($vehicle->current_mileage) }} km | {{ \App\Models\Vehicle::STATUSES[$vehicle->status] }}</div></div>
    <div class="actions"><a class="btn secondary" href="{{ route('fleet.reports.expenses', ['vehicle_id' => $vehicle->id]) }}">Expenses</a><a class="btn secondary" href="{{ route('fleet.reports.maintenance', ['vehicle_id' => $vehicle->id]) }}">Maintenance</a><a class="btn secondary" href="{{ route('fleet.reports.duty-history', ['vehicle_id' => $vehicle->id]) }}">Duty History</a><a class="btn light" href="{{ route('fleet.index') }}">Back</a></div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    @foreach(\App\Models\VehicleAssignmentHistory::ROLES as $role => $label)
        @php($active = $vehicle->assignments->first(fn($row) => $row->duty_role === $role && !$row->end_date))
        <div class="card stat"><span class="muted">Current {{ $label }}</span><strong style="font-size:17px">{{ $active?->employee?->name ?? 'Not assigned' }}</strong></div>
    @endforeach
    <div class="card stat"><span class="muted">Recorded Expenses</span><strong>{{ number_format((float)$vehicle->expenses->sum('amount'), 2) }}</strong></div>
</div>

<details class="card" style="margin-bottom:16px">
    <summary style="cursor:pointer"><strong>Edit Vehicle & Mileage</strong></summary>
    <form method="post" action="{{ route('fleet.update', $vehicle) }}" class="form-grid" style="margin-top:16px">
        @csrf @method('put')
        <div><label>Registration No</label><input name="registration_no" value="{{ old('registration_no', $vehicle->registration_no) }}" required></div>
        <div><label>Name</label><input name="name" value="{{ old('name', $vehicle->name) }}" required></div>
        <div><label>Type</label><input name="vehicle_type" value="{{ old('vehicle_type', $vehicle->vehicle_type) }}"></div>
        <div><label>Make</label><input name="make" value="{{ old('make', $vehicle->make) }}"></div>
        <div><label>Model</label><input name="model" value="{{ old('model', $vehicle->model) }}"></div>
        <div><label>Year</label><input type="number" name="year" value="{{ old('year', $vehicle->year) }}"></div>
        <div><label>Fuel Type</label><select name="fuel_type"><option value="">Select</option>@foreach(\App\Models\Vehicle::FUEL_TYPES as $key=>$label)<option value="{{ $key }}" @selected(old('fuel_type',$vehicle->fuel_type)===$key)>{{ $label }}</option>@endforeach</select></div>
        <div><label>Status</label><select name="status">@foreach(\App\Models\Vehicle::STATUSES as $key=>$label)<option value="{{ $key }}" @selected(old('status',$vehicle->status)===$key)>{{ $label }}</option>@endforeach</select></div>
        <div><label>Current Mileage</label><input type="number" min="0" name="current_mileage" value="{{ old('current_mileage',$vehicle->current_mileage) }}" required></div>
        <div><label>Chassis No</label><input name="chassis_no" value="{{ old('chassis_no',$vehicle->chassis_no) }}"></div>
        <div><label>Engine No</label><input name="engine_no" value="{{ old('engine_no',$vehicle->engine_no) }}"></div>
        <div class="full"><label>Note</label><textarea name="note">{{ old('note',$vehicle->note) }}</textarea></div>
        <div class="full"><button class="btn">Update Vehicle</button></div>
    </form>
</details>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr));margin-bottom:16px">
    <section class="card">
        <h2>Assign Driver / Helper / Supervisor</h2>
        <form method="post" action="{{ route('fleet.assignments.store', $vehicle) }}" class="form-grid">
            @csrf
            <div><label>Employee</label><select name="employee_id" required><option value="">Select</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id')==$employee->id)>{{ $employee->name }}{{ $employee->fleet_role ? ' — '.ucfirst($employee->fleet_role) : '' }}</option>@endforeach</select></div>
            <div><label>Duty Role</label><select name="duty_role" required>@foreach(\App\Models\VehicleAssignmentHistory::ROLES as $key=>$label)<option value="{{ $key }}" @selected(old('duty_role')===$key)>{{ $label }}</option>@endforeach</select></div>
            <div><label>Start Date</label><input type="date" name="start_date" value="{{ old('start_date',now()->toDateString()) }}" required></div>
            <div><label>Note</label><input name="note" value="{{ old('note') }}"></div>
            <div class="full"><button class="btn">Assign & Preserve History</button></div>
        </form>
    </section>
    <section class="card">
        <h2>Add Maintenance Schedule</h2>
        <form method="post" action="{{ route('fleet.maintenance-items.store',$vehicle) }}" class="form-grid">
            @csrf
            <div><label>Item / Check</label><input name="name" placeholder="Engine Oil, Tire Pressure, Brakes" required></div>
            <div><label>Type</label><select name="maintenance_type">@foreach(\App\Models\VehicleMaintenanceItem::TYPES as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div><label>Interval Days</label><input type="number" min="1" name="interval_days"></div>
            <div><label>Interval Mileage (km)</label><input type="number" min="1" name="interval_mileage"></div>
            <div><label>First Due Date</label><input type="date" name="next_due_date"></div>
            <div><label>First Due Mileage</label><input type="number" min="0" name="next_due_mileage"></div>
            <div class="full"><label>Note</label><input name="note"></div>
            <div class="full"><button class="btn">Add Schedule</button></div>
        </form>
    </section>
</div>

<section class="card" style="margin-bottom:16px">
    <h2>Maintenance Schedule & Due Status</h2>
    <table><thead><tr><th>Item</th><th>Type</th><th>Last Check</th><th>Last Change</th><th>Next Date</th><th>Next Mileage</th><th>Status</th></tr></thead><tbody>
    @forelse($vehicle->maintenanceItems as $item)<tr><td>{{ $item->name }}</td><td>{{ \App\Models\VehicleMaintenanceItem::TYPES[$item->maintenance_type] }}</td><td>{{ $item->last_checked_at?->format('Y-m-d') ?? 'N/A' }}</td><td>{{ $item->last_changed_at?->format('Y-m-d') ?? 'N/A' }}</td><td>{{ $item->next_due_date?->format('Y-m-d') ?? 'N/A' }}</td><td>{{ $item->next_due_mileage ? number_format($item->next_due_mileage).' km' : 'N/A' }}</td><td><span class="badge {{ $item->isDue($vehicle->current_mileage) ? 'due' : 'active' }}">{{ $item->isDue($vehicle->current_mileage) ? 'Due' : 'OK' }}</span></td></tr>
    @empty<tr><td colspan="7">No maintenance schedule yet.</td></tr>@endforelse</tbody></table>
</section>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr));margin-bottom:16px">
    <section class="card">
        <h2>Log Check / Change / Service</h2>
        <form method="post" action="{{ route('fleet.maintenance-logs.store',$vehicle) }}" class="form-grid">
            @csrf
            <div><label>Scheduled Item (Optional)</label><select name="maintenance_item_id"><option value="">General / unscheduled repair</option>@foreach($vehicle->maintenanceItems as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
            <div><label>Work / Repair Name</label><input name="work_name" placeholder="Clutch repair, body work, electrical repair"></div>
            <div><label>Action</label><select name="action">@foreach(\App\Models\VehicleMaintenanceLog::ACTIONS as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div><label>Date</label><input type="date" name="service_date" value="{{ now()->toDateString() }}" required></div>
            <div><label>Mileage</label><input type="number" min="0" name="mileage" value="{{ $vehicle->current_mileage }}"></div>
            <div><label>Cost</label><input type="number" min="0" step="0.01" name="cost" value="0" required></div>
            <div><label>Vendor</label><input name="vendor"></div>
            <div class="full"><label>Details</label><textarea name="details" rows="2"></textarea></div>
            <div class="full"><button class="btn">Save Maintenance Log</button></div>
        </form>
    </section>
    <section class="card">
        <h2>Log Daily / Trip Expense</h2>
        <form method="post" action="{{ route('fleet.expenses.store',$vehicle) }}" class="form-grid">
            @csrf
            <div><label>Category</label><select name="category">@foreach(\App\Models\VehicleExpense::CATEGORIES as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div><label>Date</label><input type="date" name="expense_date" value="{{ now()->toDateString() }}" required></div>
            <div><label>Driver / Creator</label><select name="employee_id"><option value="">Not selected</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></div>
            <div><label>Amount</label><input type="number" min="0.01" step="0.01" name="amount" required></div>
            <div><label>Quantity</label><input type="number" min="0.001" step="0.001" name="quantity"></div>
            <div><label>Unit</label><input name="unit" placeholder="Liter, pcs"></div>
            <div><label>Mileage</label><input type="number" min="0" name="mileage" value="{{ $vehicle->current_mileage }}"></div>
            <div><label>Trip Reference</label><input name="trip_reference"></div>
            <div><label>Vendor</label><input name="vendor"></div>
            <div class="full"><label>Description</label><textarea name="description" rows="2"></textarea></div>
            <div class="full"><button class="btn">Save Expense</button></div>
        </form>
    </section>
</div>

<section class="card" style="margin-bottom:16px"><h2>Assignment History</h2><table><thead><tr><th>Role</th><th>Employee</th><th>Start</th><th>End</th><th>Assigned By</th><th>Action</th></tr></thead><tbody>
@forelse($vehicle->assignments as $assignment)<tr><td>{{ ucfirst($assignment->duty_role) }}</td><td>{{ $assignment->employee->name }}</td><td>{{ $assignment->start_date->format('Y-m-d') }}</td><td>{{ $assignment->end_date?->format('Y-m-d') ?? 'Current' }}</td><td>{{ $assignment->assigner?->name ?? 'N/A' }}</td><td>@if(!$assignment->end_date)<form method="post" action="{{ route('fleet.assignments.end',$assignment) }}" class="actions">@csrf @method('patch')<input type="date" name="end_date" value="{{ now()->toDateString() }}" required style="width:150px"><button class="btn light">End Duty</button></form>@else — @endif</td></tr>
@empty<tr><td colspan="6">No assignment history.</td></tr>@endforelse</tbody></table></section>

<section class="card" style="margin-bottom:16px"><h2>Recent Maintenance</h2><table><thead><tr><th>Date</th><th>Item</th><th>Action</th><th>Mileage</th><th>Cost</th><th>Details</th></tr></thead><tbody>
@forelse($vehicle->maintenanceLogs as $log)<tr><td>{{ $log->service_date->format('Y-m-d') }}</td><td>{{ $log->item?->name ?? $log->work_name ?? 'General' }}</td><td>{{ ucfirst($log->action) }}</td><td>{{ $log->mileage ? number_format($log->mileage) : 'N/A' }}</td><td>{{ number_format((float)$log->cost,2) }}</td><td>{{ $log->details ?? 'N/A' }}</td></tr>@empty<tr><td colspan="6">No maintenance logs.</td></tr>@endforelse</tbody></table></section>

<section class="card"><h2>Recent Expenses</h2><table><thead><tr><th>Date</th><th>Category</th><th>Driver</th><th>Trip</th><th>Amount</th><th>Description</th></tr></thead><tbody>
@forelse($vehicle->expenses as $expense)<tr><td>{{ $expense->expense_date->format('Y-m-d') }}</td><td>{{ \App\Models\VehicleExpense::CATEGORIES[$expense->category] }}</td><td>{{ $expense->employee?->name ?? 'N/A' }}</td><td>{{ $expense->trip_reference ?? 'N/A' }}</td><td>{{ number_format((float)$expense->amount,2) }}</td><td>{{ $expense->description ?? 'N/A' }}</td></tr>@empty<tr><td colspan="6">No vehicle expenses.</td></tr>@endforelse</tbody></table></section>
@endsection
