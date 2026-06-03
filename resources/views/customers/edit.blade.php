@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Edit Party</h1><div class="muted">Update party details, customer/vendor roles, and internet package assignment</div></div>
    <a class="btn light" href="{{ route('customers.show', $customer) }}">Back</a>
</div>

<form method="post" action="{{ route('customers.update', $customer) }}" class="card form-grid">
    @csrf
    @method('PUT')

    <div><label>Name</label><input name="name" value="{{ old('name', $customer->name) }}" required></div>
    <div><label>Phone</label><input name="phone" value="{{ old('phone', $customer->phone) }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email', $customer->email) }}"></div>
    <div>
        <label>Connection ID</label>
        <input name="connection_id" value="{{ old('connection_id', $customer->connection_id) }}" placeholder="Only for ISP parties">
        <span class="muted">Leave blank for product-only parties. Required when assigning an internet package.</span>
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
                <option value="{{ $router->id }}" @selected((int) old('mikrotik_router_id', $customer->mikrotik_router_id) === $router->id)>
                    {{ $router->name }} - {{ $router->ip_address }}:{{ $router->api_port }}
                </option>
            @endforeach
        </select>
        <span class="muted">Used only for ISP parties with a Connection ID.</span>
    </div>
    <div class="full"><label>Address</label><textarea name="address" required>{{ old('address', $customer->address) }}</textarea></div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', $customer->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $customer->status) === 'inactive')>Inactive</option>
        </select>
    </div>
    <div>
        <label>Party Type</label>
        <label style="font-weight:400; display:flex; gap:8px; align-items:center;">
            <input type="checkbox" name="is_customer" value="1" @checked(old('is_customer', $customer->is_customer)) style="width:auto;">
            Customer
        </label>
        <label style="font-weight:400; display:flex; gap:8px; align-items:center; margin-top:6px">
            <input type="checkbox" name="is_vendor" value="1" @checked(old('is_vendor', $customer->is_vendor)) style="width:auto;">
            Vendor / Wholesale shop
        </label>
    </div>
    <div>
        <label>Special ISP Customer</label>
        <label style="font-weight:400; display:flex; gap:8px; align-items:center;">
            <input type="checkbox" name="never_suspend" value="1" @checked(old('never_suspend', $customer->never_suspend)) style="width:auto;">
            Never close line and auto-generate monthly bill
        </label>
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
    <div class="full"><button class="btn" type="submit">Update Party</button></div>
</form>
@endsection
