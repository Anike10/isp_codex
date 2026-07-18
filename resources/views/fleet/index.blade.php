@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Vehicle & Fleet Management</h1><div class="muted">Vehicles, current staff, maintenance due status, mileage, and operating cost</div></div>
    <div class="actions"><a class="btn" href="{{ route('fleet.create') }}">Add Vehicle</a><a class="btn secondary" href="{{ route('fleet.reports') }}">Fleet Reports</a></div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Total Vehicles</span><strong>{{ $vehicleCount }}</strong></div>
    <div class="card stat"><span class="muted">Active</span><strong>{{ $activeCount }}</strong></div>
    <div class="card stat"><span class="muted">In Maintenance</span><strong>{{ $maintenanceCount }}</strong></div>
    <div class="card stat"><span class="muted">Service Items Due</span><strong>{{ $dueCount }}</strong></div>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Registration, name, make, model"></div>
    <div><label>Status</label><select name="status"><option value="">All</option>@foreach(\App\Models\Vehicle::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Expense From</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>Expense To</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div class="full actions"><button class="btn secondary">Filter</button><a class="btn light" href="{{ route('fleet.index') }}">Reset</a></div>
</form>

@include('partials.per_page')
<table>
    <thead><tr><th>Vehicle</th><th>Status</th><th>Mileage</th><th>Current Staff</th><th>Maintenance</th><th>Filtered Expense</th><th>Action</th></tr></thead>
    <tbody>
    @forelse($vehicles as $vehicle)
        <tr data-href="{{ route('fleet.show', $vehicle) }}">
            <td><strong>{{ $vehicle->registration_no }}</strong><div>{{ $vehicle->name }}</div><div class="muted">{{ trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')) ?: 'N/A' }}</div></td>
            <td><span class="badge {{ $vehicle->status === 'active' ? 'active' : ($vehicle->status === 'maintenance' ? 'pending' : '') }}">{{ \App\Models\Vehicle::STATUSES[$vehicle->status] }}</span></td>
            <td>{{ number_format($vehicle->current_mileage) }} km</td>
            <td>@forelse($vehicle->activeAssignments as $assignment)<div><strong>{{ ucfirst($assignment->duty_role) }}:</strong> {{ $assignment->employee->name }}</div>@empty<span class="muted">Not assigned</span>@endforelse</td>
            <td>@if($vehicle->due_maintenance_count)<span class="badge due">{{ $vehicle->due_maintenance_count }} due</span>@else<span class="badge active">Up to date</span>@endif</td>
            <td>{{ number_format((float)$vehicle->filtered_expense_total, 2) }}</td>
            <td><a class="btn light" href="{{ route('fleet.show', $vehicle) }}">Open</a></td>
        </tr>
    @empty<tr><td colspan="7">No vehicle found.</td></tr>@endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $vehicles->links() }}</div>
@endsection
