@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Edit Party</h1><div class="muted">Update party details, reseller assignment, wallet limit, and internet package</div></div>
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
    <div>
        <label>IP Assignment</label>
        <label style="font-weight:400; display:flex; gap:8px; align-items:center;">
            <input id="use-fixed-ip" type="checkbox" name="use_fixed_ip" value="1" @checked(old('use_fixed_ip', $customer->use_fixed_ip)) style="width:auto;">
            Always use a fixed IP
        </label>
        <span class="muted">Dynamic mode learns a fresh IP after a package change and stores it in the MikroTik secret.</span>
    </div>
    <div>
        <label>Fixed IP Address</label>
        <input id="fixed-ip-address" name="fixed_ip_address" value="{{ old('fixed_ip_address', $customer->fixed_ip_address) }}" placeholder="Example: 10.10.10.25">
        <span class="muted">Required only when Fixed IP is selected.</span>
    </div>
    <div class="full"><label>Address</label><textarea name="address" required>{{ old('address', $customer->address) }}</textarea></div>
    <div class="full"><label>Note</label><textarea name="notes">{{ old('notes', $customer->notes) }}</textarea></div>
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
        <label style="font-weight:400; display:flex; gap:8px; align-items:center; margin-top:6px">
            <input id="is-reseller" type="checkbox" name="is_reseller" value="1" @checked(old('is_reseller', $customer->is_reseller)) style="width:auto;">
            Reseller
        </label>
    </div>
    <div>
        <label>Assigned Reseller</label>
        <select id="reseller-id" name="reseller_id">
            <option value="">Direct customer / no reseller</option>
            @foreach ($resellers as $reseller)
                <option value="{{ $reseller->id }}" @selected((int) old('reseller_id', $customer->reseller_id) === $reseller->id)>{{ $reseller->name }} — {{ $reseller->phone }}</option>
            @endforeach
        </select>
        <span class="muted">A reseller account itself cannot be assigned under another reseller.</span>
    </div>
    <div>
        <label>Reseller Daily Payment Limit</label>
        <input id="reseller-limit" type="number" min="1" step="0.01" name="reseller_daily_payment_limit" value="{{ old('reseller_daily_payment_limit', $customer->reseller_daily_payment_limit) }}" placeholder="Blank means unlimited">
    </div>
    <div>
        <label>Reseller Commission (%)</label>
        <input id="reseller-commission" type="number" min="0" max="100" step="0.01" name="reseller_commission_percent" value="{{ old('reseller_commission_percent', $customer->reseller_commission_percent) }}">
        <span class="muted">Changes are saved in commission history; old invoices keep their original percentage.</span>
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
<script>
const fixedIpToggle = document.getElementById('use-fixed-ip');
const fixedIpInput = document.getElementById('fixed-ip-address');
const updateFixedIpInput = () => { fixedIpInput.required = fixedIpToggle.checked; };
fixedIpToggle.addEventListener('change', updateFixedIpInput);
updateFixedIpInput();
const resellerToggle = document.getElementById('is-reseller');
const resellerSelect = document.getElementById('reseller-id');
const resellerLimit = document.getElementById('reseller-limit');
const resellerCommission = document.getElementById('reseller-commission');
const updateResellerFields = () => {
    resellerSelect.disabled = resellerToggle.checked;
    resellerLimit.disabled = ! resellerToggle.checked;
    resellerCommission.disabled = ! resellerToggle.checked;
};
resellerToggle.addEventListener('change', updateResellerFields);
updateResellerFields();
</script>
@endsection
