@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Customer</h1><div class="muted">Create a customer and optionally assign an internet package</div></div>
    <a class="btn light" href="{{ route('customers.index') }}">Back</a>
</div>

<form method="post" action="{{ route('customers.store') }}" class="card form-grid">
    @csrf
    <div><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
    <div><label>Phone</label><input name="phone" value="{{ old('phone') }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}"></div>
    <div><label>Connection ID</label><input name="connection_id" value="{{ old('connection_id') }}" required></div>
    <div class="full"><label>Address</label><textarea name="address" required>{{ old('address') }}</textarea></div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    <div>
        <label>Internet Package</label>
        <select name="internet_package_id">
            <option value="">No package yet</option>
            @foreach ($packages as $package)
                <option value="{{ $package->id }}">{{ $package->name }} - {{ $package->speed }} - {{ number_format($package->monthly_price, 2) }}</option>
            @endforeach
        </select>
    </div>
    <div><label>Connection Start Date</label><input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}"></div>
    <div class="full"><button class="btn" type="submit">Save Customer</button></div>
</form>
@endsection
