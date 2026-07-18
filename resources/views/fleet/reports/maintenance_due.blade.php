@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Maintenance Due & Overdue Report</h1><div class="muted">Days and mileage remaining for every periodic vehicle maintenance item</div></div><a class="btn secondary" href="{{ route('fleet.maintenance.schedules') }}">Manage Schedules</a></div>
@include('fleet.reports._nav')

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Overdue</span><strong>{{ $statusCounts['overdue'] ?? 0 }}</strong></div>
    <div class="card stat"><span class="muted">Due Now</span><strong>{{ $statusCounts['due'] ?? 0 }}</strong></div>
    <div class="card stat"><span class="muted">Upcoming</span><strong>{{ $statusCounts['upcoming'] ?? 0 }}</strong></div>
    <div class="card stat"><span class="muted">Unscheduled</span><strong>{{ $statusCounts['unscheduled'] ?? 0 }}</strong></div>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div><label>Vehicle</label><select name="vehicle_id"><option value="">All vehicles</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((int)request('vehicle_id')===$vehicle->id)>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</option>@endforeach</select></div>
    <div><label>Status</label><select name="status"><option value="">All</option><option value="overdue" @selected(request('status')==='overdue')>Overdue</option><option value="due" @selected(request('status')==='due')>Due Now</option><option value="upcoming" @selected(request('status')==='upcoming')>Upcoming</option><option value="unscheduled" @selected(request('status')==='unscheduled')>Unscheduled</option></select></div>
    <div><label>Type</label><select name="type"><option value="">All</option>@foreach(\App\Models\VehicleMaintenanceItem::TYPES as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Search Item</label><input name="search" value="{{ request('search') }}"></div>
    <div class="full actions"><button class="btn">Apply Filters</button><a class="btn light" href="{{ route('fleet.reports.maintenance-due') }}">Reset</a></div>
</form>

@include('fleet.maintenance._schedule_table')
@endsection
