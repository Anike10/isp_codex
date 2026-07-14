@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Vehicle</h1><div class="muted">Add a car, pickup, truck, motorcycle, van, or any other fleet vehicle</div></div>
    <a class="btn light" href="{{ route('fleet.index') }}">Back to Vehicles</a>
</div>

<form method="post" action="{{ route('fleet.store') }}" class="card form-grid">
    @csrf
    <div><label>Registration No</label><input name="registration_no" value="{{ old('registration_no') }}" placeholder="DHAKA-METRO-11-1234" required></div>
    <div><label>Vehicle Name</label><input name="name" value="{{ old('name') }}" placeholder="Field Pickup 01" required></div>
    <div><label>Vehicle Type</label><input name="vehicle_type" value="{{ old('vehicle_type') }}" placeholder="Car, Pickup, Truck, Motorcycle, Van"></div>
    <div><label>Make / Brand</label><input name="make" value="{{ old('make') }}" placeholder="Toyota, Tata, Honda"></div>
    <div><label>Model</label><input name="model" value="{{ old('model') }}"></div>
    <div><label>Year</label><input type="number" name="year" min="1900" max="{{ now()->year + 1 }}" value="{{ old('year') }}"></div>
    <div><label>Fuel Type</label><select name="fuel_type"><option value="">Select fuel type</option>@foreach(\App\Models\Vehicle::FUEL_TYPES as $key => $label)<option value="{{ $key }}" @selected(old('fuel_type') === $key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Status</label><select name="status" required>@foreach(\App\Models\Vehicle::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('status', 'active') === $key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Current Mileage (km)</label><input type="number" name="current_mileage" min="0" value="{{ old('current_mileage', 0) }}" required></div>
    <div><label>Chassis No</label><input name="chassis_no" value="{{ old('chassis_no') }}"></div>
    <div><label>Engine No</label><input name="engine_no" value="{{ old('engine_no') }}"></div>
    <div class="full"><label>Note</label><textarea name="note" rows="3">{{ old('note') }}</textarea></div>
    <div class="full actions"><button class="btn" type="submit">Save Vehicle</button><a class="btn light" href="{{ route('fleet.index') }}">Cancel</a></div>
</form>
@endsection
