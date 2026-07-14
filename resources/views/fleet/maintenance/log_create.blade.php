@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Log Repair / Maintenance</h1><div class="muted">Record what was checked, changed, serviced, or repaired on a vehicle</div></div><div class="actions"><a class="btn secondary" href="{{ route('fleet.maintenance.schedules') }}">Maintenance Schedules</a><a class="btn light" href="{{ route('fleet.index') }}">Vehicles</a></div></div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Select Vehicle First</label><select name="vehicle_id" required onchange="this.form.submit()"><option value="">Select vehicle</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected($selectedVehicle?->id===$vehicle->id)>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</option>@endforeach</select></div>
    <div class="actions" style="align-self:end"><button class="btn secondary">Load Maintenance Items</button></div>
</form>

@if($selectedVehicle)
<div class="grid stats" style="margin-bottom:16px"><div class="card stat"><span class="muted">Vehicle</span><strong style="font-size:18px">{{ $selectedVehicle->registration_no }}</strong></div><div class="card stat"><span class="muted">Current Mileage</span><strong>{{ number_format($selectedVehicle->current_mileage) }} km</strong></div></div>
<form method="post" action="{{ route('fleet.maintenance.logs.store') }}" class="card form-grid">
    @csrf
    <input type="hidden" name="vehicle_id" value="{{ $selectedVehicle->id }}">
    <div><label>Scheduled Item (Optional)</label><select name="maintenance_item_id"><option value="">General / unscheduled repair</option>@foreach($maintenanceItems as $item)<option value="{{ $item->id }}" @selected((int)old('maintenance_item_id',request('maintenance_item_id'))===$item->id)>{{ $item->name }} — {{ ucfirst($item->dueStatus($selectedVehicle->current_mileage)) }}</option>@endforeach</select></div>
    <div><label>Work / Repair Name</label><input name="work_name" value="{{ old('work_name') }}" placeholder="Clutch repair, body work, electrical repair"></div>
    <div><label>Work Type</label><select name="action">@foreach(\App\Models\VehicleMaintenanceLog::ACTIONS as $key=>$label)<option value="{{ $key }}" @selected(old('action')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Work Date</label><input type="date" name="service_date" value="{{ old('service_date',now()->toDateString()) }}" required></div>
    <div><label>Mileage When Done</label><input type="number" min="0" name="mileage" value="{{ old('mileage',$selectedVehicle->current_mileage) }}"></div>
    <div><label>Cost</label><input type="number" min="0" step="0.01" name="cost" value="{{ old('cost',0) }}" required></div>
    <div><label>Workshop / Vendor</label><input name="vendor" value="{{ old('vendor') }}"></div>
    <div class="full"><label>What Was Done</label><textarea name="details" rows="3" placeholder="Parts changed, condition found, repair details">{{ old('details') }}</textarea></div>
    <div class="full"><button class="btn">Save Work / Maintenance</button></div>
</form>
@endif
@endsection
