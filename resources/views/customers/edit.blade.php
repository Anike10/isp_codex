@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Edit Customer</h1><div class="muted">Update profile details and assign an internet package</div></div>
    <a class="btn light" href="{{ route('customers.show', $customer) }}">Back</a>
</div>

<form method="post" action="{{ route('customers.update', $customer) }}" class="card form-grid">
    @csrf
    @method('PUT')

    <div><label>Name</label><input name="name" value="{{ old('name', $customer->name) }}" required></div>
    <div><label>Phone</label><input name="phone" value="{{ old('phone', $customer->phone) }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email', $customer->email) }}"></div>
    <div><label>Connection ID</label><input name="connection_id" value="{{ old('connection_id', $customer->connection_id) }}" required></div>
    <div class="full"><label>Address</label><textarea name="address" required>{{ old('address', $customer->address) }}</textarea></div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', $customer->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $customer->status) === 'inactive')>Inactive</option>
        </select>
    </div>
    <div>
        <label>Internet Package</label>
        <select name="internet_package_id">
            <option value="">No active package</option>
            @foreach ($packages as $package)
                <option value="{{ $package->id }}" @selected((int) old('internet_package_id', $customer->activeSubscription?->internet_package_id) === $package->id)>
                    {{ $package->name }} - {{ $package->speed }} - {{ number_format($package->monthly_price, 2) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Connection Start Date</label>
        <input type="date" name="start_date" value="{{ old('start_date', $customer->activeSubscription?->start_date?->format('Y-m-d') ?? now()->toDateString()) }}">
    </div>
    <div class="full"><button class="btn" type="submit">Update Customer</button></div>
</form>
@endsection
