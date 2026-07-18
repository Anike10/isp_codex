@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Maintenance Schedules</h1><div class="muted">Define periodic checks/replacements and see what is upcoming, due, or overdue</div></div><div class="actions"><a class="btn secondary" href="{{ route('fleet.maintenance.logs.create') }}">Log Repair / Maintenance</a><a class="btn light" href="{{ route('fleet.index') }}">Vehicles</a></div></div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Overdue</span><strong>{{ $statusCounts['overdue'] ?? 0 }}</strong></div>
    <div class="card stat"><span class="muted">Due Now</span><strong>{{ $statusCounts['due'] ?? 0 }}</strong></div>
    <div class="card stat"><span class="muted">Upcoming</span><strong>{{ $statusCounts['upcoming'] ?? 0 }}</strong></div>
    <div class="card stat"><span class="muted">Unscheduled</span><strong>{{ $statusCounts['unscheduled'] ?? 0 }}</strong></div>
</div>

<details class="card" style="margin-bottom:16px" @if($errors->any()) open @endif>
    <summary style="cursor:pointer"><strong>Add Periodic Maintenance Schedule</strong></summary>
    <form method="post" action="{{ route('fleet.maintenance.schedules.store') }}" class="form-grid" style="margin-top:16px">
        @csrf
        <div><label>Vehicle</label><select name="vehicle_id" required><option value="">Select vehicle</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((int)old('vehicle_id',request('vehicle_id'))===$vehicle->id)>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</option>@endforeach</select></div>
        <div><label>Maintenance Item</label><input name="name" value="{{ old('name') }}" placeholder="Engine Oil, Air Filter, Tire Pressure, Brakes" required></div>
        <div><label>Type</label><select name="maintenance_type">@foreach(\App\Models\VehicleMaintenanceItem::TYPES as $key=>$label)<option value="{{ $key }}" @selected(old('maintenance_type')===$key)>{{ $label }}</option>@endforeach</select></div>
        <div><label>Repeat Every (Days)</label><input type="number" min="1" name="interval_days" value="{{ old('interval_days') }}" placeholder="90"></div>
        <div><label>Repeat Every (Mileage)</label><input type="number" min="1" name="interval_mileage" value="{{ old('interval_mileage') }}" placeholder="5000"></div>
        <div><label>First / Next Due Date</label><input type="date" name="next_due_date" value="{{ old('next_due_date') }}"></div>
        <div><label>First / Next Due Mileage</label><input type="number" min="0" name="next_due_mileage" value="{{ old('next_due_mileage') }}"></div>
        <div><label>Note</label><input name="note" value="{{ old('note') }}"></div>
        <div class="full"><button class="btn">Save Schedule</button></div>
    </form>
</details>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div><label>Vehicle</label><select name="vehicle_id"><option value="">All vehicles</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((int)request('vehicle_id')===$vehicle->id)>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</option>@endforeach</select></div>
    <div><label>Status</label><select name="status"><option value="">All</option><option value="overdue" @selected(request('status')==='overdue')>Overdue</option><option value="due" @selected(request('status')==='due')>Due Now</option><option value="upcoming" @selected(request('status')==='upcoming')>Upcoming</option><option value="unscheduled" @selected(request('status')==='unscheduled')>Unscheduled</option></select></div>
    <div><label>Type</label><select name="type"><option value="">All</option>@foreach(\App\Models\VehicleMaintenanceItem::TYPES as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Search Item</label><input name="search" value="{{ request('search') }}"></div>
    <div class="full actions"><button class="btn secondary">Filter</button><a class="btn light" href="{{ route('fleet.maintenance.schedules') }}">Reset</a></div>
</form>

@include('fleet.maintenance._schedule_table')
@endsection
