@extends('layouts.app')

@section('main_class', 'party-edit-page')
@section('content')
@php
    $savedRouterIds = $customer->mikrotikRouters->pluck('id')->map(fn ($id) => (int) $id)->all();
    if ($savedRouterIds === [] && $customer->mikrotik_router_id) {
        $savedRouterIds = [(int) $customer->mikrotik_router_id];
    }
    $selectedRouterIds = array_map('intval', old('mikrotik_router_ids', $savedRouterIds));
@endphp

<link rel="stylesheet" href="{{ asset('css/customer-edit.css') }}?v=20260813-2">

<div class="party-edit-shell">
    <header class="party-edit-header">
        <div class="party-edit-identity">
            <a class="party-edit-back" href="{{ route('customers.show', $customer) }}" aria-label="Back to party profile">&larr;</a>
            <div>
                <span class="party-edit-eyebrow">Party #{{ $customer->id }}</span>
                <h1>Edit {{ $customer->name }}</h1>
                <p>{{ $customer->connection_id ?: 'No connection ID' }} <span aria-hidden="true">/</span> {{ $customer->phone ?: 'No phone' }}</p>
            </div>
        </div>
        <details class="party-edit-help">
            <summary aria-label="Open edit form help">?</summary>
            <div>
                <strong>Quick guide</strong>
                <p>Update identity and service settings in their cards. Use the map panel for the exact service location, then save once.</p>
            </div>
        </details>
    </header>

    <form method="post" action="{{ route('customers.update', $customer) }}" class="party-edit-form">
        @csrf
        @method('PUT')

        <section class="edit-panel" aria-labelledby="basic-panel-title">
            <header class="edit-panel-head">
                <span class="edit-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0"/></svg>
                </span>
                <div>
                    <h2 id="basic-panel-title">Basic Information</h2>
                    <p>Identity, contact details and party classification.</p>
                </div>
            </header>

            <div class="edit-field-grid">
                <label class="edit-field">
                    <span>Name <b>*</b></span>
                    <input name="name" value="{{ old('name', $customer->name) }}" required autocomplete="name">
                </label>

                <label class="edit-field">
                    <span class="label-with-badge">Phone <em>Multiple allowed</em></span>
                    <input name="phone" value="{{ old('phone', $customer->phone) }}" autocomplete="tel">
                    <small>Separate numbers with commas or spaces, e.g. 01710000000, 01820000000.</small>
                </label>

                <label class="edit-field">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" autocomplete="email" placeholder="name@example.com">
                </label>

                <label class="edit-field">
                    <span>Status <b>*</b></span>
                    <select name="status" required>
                        <option value="active" @selected(old('status', $customer->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $customer->status) === 'inactive')>Inactive</option>
                    </select>
                </label>

                <label class="edit-field edit-field--wide">
                    <span>Address</span>
                    <textarea name="address" rows="3" autocomplete="street-address">{{ old('address', $customer->address) }}</textarea>
                </label>

                <label class="edit-field edit-field--wide">
                    <span>Internal Note</span>
                    <textarea name="notes" rows="3" placeholder="Optional internal note">{{ old('notes', $customer->notes) }}</textarea>
                </label>
            </div>

            <div class="edit-subsection">
                <div class="edit-subsection-head">
                    <h3>Party Role &amp; Reseller</h3>
                    <p>Choose every role that applies to this party.</p>
                </div>
                <div class="role-choice-grid">
                    <label class="choice-tile">
                        <input type="checkbox" name="is_customer" value="1" @checked(old('is_customer', $customer->is_customer))>
                        <span><strong>Customer</strong><small>Receives invoices and service</small></span>
                    </label>
                    <label class="choice-tile">
                        <input type="checkbox" name="is_vendor" value="1" @checked(old('is_vendor', $customer->is_vendor))>
                        <span><strong>Vendor</strong><small>Wholesale or supplier party</small></span>
                    </label>
                    <label class="choice-tile">
                        <input id="is-reseller" type="checkbox" name="is_reseller" value="1" @checked(old('is_reseller', $customer->is_reseller))>
                        <span><strong>Reseller</strong><small>Can manage assigned customers</small></span>
                    </label>
                </div>

                <div class="edit-field-grid edit-field-grid--compact">
                    <label class="edit-field">
                        <span>Assigned Reseller</span>
                        <select id="reseller-id" name="reseller_id">
                            <option value="">Direct customer / no reseller</option>
                            @foreach ($resellers as $reseller)
                                <option value="{{ $reseller->id }}" @selected((int) old('reseller_id', $customer->reseller_id) === $reseller->id)>{{ $reseller->name }} - {{ $reseller->phone }}</option>
                            @endforeach
                        </select>
                        <small>A reseller account cannot be assigned below another reseller.</small>
                    </label>
                    <label class="edit-field">
                        <span>Daily Payment Limit</span>
                        <input id="reseller-limit" type="number" min="1" step="0.01" name="reseller_daily_payment_limit" value="{{ old('reseller_daily_payment_limit', $customer->reseller_daily_payment_limit) }}" placeholder="Blank means unlimited">
                    </label>
                    <label class="edit-field">
                        <span>Commission (%)</span>
                        <input id="reseller-commission" type="number" min="0" max="100" step="0.01" name="reseller_commission_percent" value="{{ old('reseller_commission_percent', $customer->reseller_commission_percent) }}">
                        <small>Changes are retained in commission history.</small>
                    </label>
                </div>
            </div>
        </section>

        <section class="edit-panel" aria-labelledby="network-panel-title">
            <header class="edit-panel-head edit-panel-head--network">
                <span class="edit-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 15a7 7 0 0 1 14 0M8 18a4 4 0 0 1 8 0m-4 2h.01M3 11a11 11 0 0 1 18 0"/></svg>
                </span>
                <div>
                    <h2 id="network-panel-title">ISP &amp; Network Settings</h2>
                    <p>Connection, MikroTik targets, IP assignment and service package.</p>
                </div>
            </header>

            <div class="edit-field-grid">
                <label class="edit-field">
                    <span>Connection ID</span>
                    <input name="connection_id" value="{{ old('connection_id', $customer->connection_id) }}" placeholder="Required for ISP service">
                    <small>Leave empty only for product-only parties.</small>
                </label>

                <div class="edit-field">
                    <span>MikroTik Password</span>
                    <div class="readonly-secret"><code>4321</code><em>Read only</em></div>
                    <small>Default credential used when a Connection ID is assigned.</small>
                </div>

                <div class="edit-field edit-field--wide">
                    <span>MikroTik Router Targets</span>
                    <div class="router-choice-grid">
                        @forelse ($routers as $router)
                            <label class="router-choice">
                                <input type="checkbox" name="mikrotik_router_ids[]" value="{{ $router->id }}" @checked(in_array($router->id, $selectedRouterIds, true))>
                                <span class="router-choice-check" aria-hidden="true"></span>
                                <span>
                                    <strong>{{ $router->name }}</strong>
                                    <small>{{ $router->ip_address }}:{{ $router->api_port }}</small>
                                </span>
                            </label>
                        @empty
                            <p class="empty-choice">No active MikroTik router is available.</p>
                        @endforelse
                    </div>
                    <small>Select one or more targets. The PPPoE user is synchronized to every selected router.</small>
                </div>
            </div>

            <div class="network-option-grid">
                <div class="network-option-card">
                    <div class="toggle-line">
                        <div>
                            <strong>Fixed IP Assignment</strong>
                            <small>Keep one IP instead of learning a dynamic address.</small>
                        </div>
                        <label class="switch-control">
                            <input id="use-fixed-ip" type="checkbox" name="use_fixed_ip" value="1" @checked(old('use_fixed_ip', $customer->use_fixed_ip))>
                            <span aria-hidden="true"></span>
                        </label>
                    </div>
                    <label class="edit-field">
                        <span>Fixed IP Address</span>
                        <input id="fixed-ip-address" name="fixed_ip_address" value="{{ old('fixed_ip_address', $customer->fixed_ip_address) }}" placeholder="Example: 10.10.10.25">
                        <small>Required only while Fixed IP is enabled.</small>
                    </label>
                </div>

                <div class="network-option-card">
                    <div class="toggle-line">
                        <div>
                            <strong>Special ISP Customer</strong>
                            <small>Keep service open and generate the monthly bill.</small>
                        </div>
                        <label class="switch-control">
                            <input type="checkbox" name="never_suspend" value="1" @checked(old('never_suspend', $customer->never_suspend))>
                            <span aria-hidden="true"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="edit-field-grid edit-field-grid--service">
                <label class="edit-field">
                    <span>Internet Package</span>
                    <select name="internet_package_id">
                        <option value="">No active package</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected((int) old('internet_package_id', $customer->activeSubscription?->internet_package_id) === $package->id)>
                                {{ $package->name }} - {{ $package->speed }} - {{ number_format($package->monthly_price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="edit-field">
                    <span>Connection Start Date</span>
                    <input type="date" name="start_date" value="{{ old('start_date', $customer->activeSubscription?->start_date?->format('Y-m-d') ?? now()->toDateString()) }}">
                </label>
            </div>
        </section>

        <details class="edit-panel edit-panel--map" open>
            <summary>
                <span class="edit-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m9 18-6 3V6l6-3 6 3 6-3v15l-6 3-6-3Zm0 0V3m6 18V6"/></svg>
                </span>
                <span>
                    <strong>Map Location Picker</strong>
                    <small>Click to collapse or expand the exact service location.</small>
                </span>
                <i aria-hidden="true"></i>
            </summary>
            <div class="edit-map-body">
                @include('customers.partials.map_location', ['editable' => true])
            </div>
        </details>

        <footer class="party-edit-actions">
            <div>
                <strong>Ready to save?</strong>
                <span>All cards are submitted together.</span>
            </div>
            <div>
                <a class="btn light" href="{{ route('customers.show', $customer) }}">Cancel / Back</a>
                <button class="party-save-button" type="submit">Save Changes</button>
            </div>
        </footer>
    </form>
</div>

<script>
const fixedIpToggle = document.getElementById('use-fixed-ip');
const fixedIpInput = document.getElementById('fixed-ip-address');
const updateFixedIpInput = () => {
    fixedIpInput.required = fixedIpToggle.checked;
    fixedIpInput.closest('.edit-field').classList.toggle('is-disabled', ! fixedIpToggle.checked);
};
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
