@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Staff Duty History Report</h1><div class="muted">Current and previous Driver, Helper and Supervisor assignments</div></div><a class="btn light" href="{{ route('fleet.index') }}">Vehicles</a></div>
@include('fleet.reports._nav')

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div><label>Duty Active From</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>Duty Active To</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Vehicle</label><select name="vehicle_id"><option value="">All vehicles</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((int)request('vehicle_id')===$vehicle->id)>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</option>@endforeach</select></div>
    <div><label>Employee</label><select name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int)request('employee_id')===$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
    <div><label>Duty Role</label><select name="duty_role"><option value="">All roles</option>@foreach(\App\Models\VehicleAssignmentHistory::ROLES as $key=>$label)<option value="{{ $key }}" @selected(request('duty_role')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Status</label><select name="status"><option value="">All</option><option value="current" @selected(request('status')==='current')>Current Duty</option><option value="ended" @selected(request('status')==='ended')>Ended Duty</option></select></div>
    <div class="full actions"><button class="btn">Apply Filters</button><a class="btn light" href="{{ route('fleet.reports.duty-history') }}">Reset</a></div>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Duty Records</span><strong>{{ $dutyCount }}</strong></div>
    <div class="card stat"><span class="muted">Current in Result</span><strong>{{ $currentCount }}</strong></div>
</div>

<section class="card">@include('partials.per_page')<table><thead><tr><th>Vehicle</th><th>Role</th><th>Employee</th><th>Start</th><th>End</th><th>Status</th><th>Assigned By</th><th>Note</th></tr></thead><tbody>
@forelse($assignments as $assignment)<tr><td><a href="{{ route('fleet.show',$assignment->vehicle) }}">{{ $assignment->vehicle->registration_no }}</a></td><td>{{ \App\Models\VehicleAssignmentHistory::ROLES[$assignment->duty_role] }}</td><td>{{ $assignment->employee->name }}</td><td>{{ $assignment->start_date->format('Y-m-d') }}</td><td>{{ $assignment->end_date?->format('Y-m-d') ?? 'Current' }}</td><td><span class="badge {{ $assignment->end_date ? 'inactive' : 'active' }}">{{ $assignment->end_date ? 'Ended' : 'Current' }}</span></td><td>{{ $assignment->assigner?->name ?? 'N/A' }}</td><td>{{ $assignment->note ?? 'N/A' }}</td></tr>@empty<tr><td colspan="8">No duty history found.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $assignments->links() }}</div></section>
@endsection
