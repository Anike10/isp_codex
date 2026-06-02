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
    <div>
        <label>Connection ID</label>
        <input name="connection_id" value="{{ old('connection_id') }}" placeholder="Only for ISP customers">
        <span class="muted">Leave blank for product-only customers. Required when assigning an internet package.</span>
    </div>
    <div>
        <label>MikroTik Password</label>
        <input value="4321" readonly>
        <span class="muted">Used only when a Connection ID is provided.</span>
    </div>
    <div>
        <label>MikroTik Target</label>
        <select name="mikrotik_router_id">
            <option value="">All active MikroTik routers</option>
            @foreach ($routers as $router)
                <option value="{{ $router->id }}" @selected((int) old('mikrotik_router_id') === $router->id)>
                    {{ $router->name }} - {{ $router->ip_address }}:{{ $router->api_port }}
                </option>
            @endforeach
        </select>
        <span class="muted">Used only for ISP customers with a Connection ID.</span>
    </div>
    <div class="full"><label>Address</label><textarea name="address" required>{{ old('address') }}</textarea></div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    <div>
        <label>Special Customer</label>
        <label style="font-weight:400; display:flex; gap:8px; align-items:center;">
            <input type="checkbox" name="never_suspend" value="1" @checked(old('never_suspend')) style="width:auto;">
            Never close line and auto-generate monthly bill
        </label>
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
