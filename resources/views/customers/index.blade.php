@extends('layouts.app')

@section('main_class', 'olt-onus-wide')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $showDeletedCustomers = $showDeletedCustomers ?? false;
    $mayGrantGrace = (bool) auth()->user()?->hasPermission('grant_grace_period');
    $maySpecial = (bool) auth()->user()?->hasPermission('mark_special_customer');
    $maySetSpecialPrice = (bool) auth()->user()?->hasPermission('set_special_package_price');
@endphp
<style>
    .customer-filter-form {
        display: grid;
        grid-template-columns: minmax(190px, 1.5fr) repeat(6, minmax(110px, 1fr)) auto;
        gap: 8px;
        align-items: end;
    }
    .customer-filter-form input,
    .customer-filter-form select {
        padding: 7px 9px;
        min-height: 34px;
        font-size: 13px;
    }
    .customer-filter-form label {
        font-size: 12px;
        margin-bottom: 4px;
    }
    .customer-filter-form .filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: nowrap;
        white-space: nowrap;
    }
    .customer-filter-form .filter-actions .btn { padding: 8px 12px; }
    .customer-action-menu {
        position: relative;
        display: inline-block;
    }
    .customer-row-actions {
        vertical-align: middle;
        text-align: right;
        white-space: nowrap;
        overflow: visible;
        position: relative;
    }
    .customer-table {
        overflow: visible;
    }
    .customer-table th,
    .customer-table td {
        overflow: visible;
    }
    .customer-table th.col-center,
    .customer-table td.col-center {
        text-align: center;
        vertical-align: middle;
    }
    .customer-table td.col-center .special-toggle-form { display: block; margin: 4px auto 0; }
    .customer-action-menu > summary {
        min-width: 88px;
        cursor: pointer;
        list-style: none;
        user-select: none;
    }
    .customer-action-menu > summary::-webkit-details-marker { display: none; }
    .customer-action-menu > summary::after {
        margin-left: 7px;
        content: '\25BE';
        font-size: 11px;
    }
    .customer-action-menu[open] > summary {
        border-color: #116149;
        background: #e7f7ef;
        color: #07543e;
    }
    .customer-action-menu-list {
        position: absolute;
        top: calc(100% + 5px);
        right: 0;
        z-index: 30;
        display: grid;
        min-width: 150px;
        overflow: hidden;
        border: 1px solid #d8dee9;
        border-radius: 7px;
        background: #ffffff;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .2);
    }
    .customer-action-menu:not([open]) > .customer-action-menu-list {
        display: none;
    }
    .customer-action-menu-list a,
    .customer-action-menu-list button {
        display: block;
        width: 100%;
        min-height: 36px;
        border: 0;
        border-bottom: 1px solid #eef2f6;
        border-radius: 0;
        padding: 9px 12px;
        background: #ffffff;
        color: #172033;
        cursor: pointer;
        font: inherit;
        font-size: 13px;
        font-weight: 700;
        text-align: left;
        text-decoration: none;
    }
    .customer-action-menu-list > :last-child,
    .customer-action-menu-list > :last-child button { border-bottom: 0; }
    .customer-action-menu-list a:hover,
    .customer-action-menu-list button:hover {
        background: #eef8f4;
        color: #07543e;
    }
    .customer-action-menu-list .customer-action-delete { color: #b42318; }
    .customer-action-menu-list form { margin: 0; }
    .special-toggle-form { margin: 4px 0 0; }
    .special-toggle-btn {
        cursor: pointer;
        border: 1px solid #cdd9e6;
        background: #f4f7fb;
        color: #33475f;
        border-radius: 999px;
        padding: 2px 9px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.5;
    }
    .special-toggle-btn:hover { background: #e7eef7; }
    .customer-pay-line {
        display: block;
        margin-top: 6px;
    }
    .customer-pay-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 12px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 999px;
        line-height: 1.6;
        white-space: nowrap;
    }
    .customer-pay-btn .customer-pay-amt {
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .special-toggle-btn.is-on {
        border-color: #f0c98b;
        background: #fff4e2;
        color: #92561a;
    }
    .pkg-price { font-size: 12px; color: #667085; margin-top: 2px; }
    .pkg-price-sp { display: none; }
    .pkg-price.has-sp .pkg-price-list { text-decoration: line-through; opacity: .55; }
    .pkg-price.has-sp .pkg-price-sp { display: inline; font-weight: 700; color: #1f2937; }
    .sp-inline {
        cursor: pointer;
        margin-left: 8px;
        font-size: 11px;
        font-weight: 700;
        color: #5b6b7f;
        white-space: nowrap;
        user-select: none;
        border-bottom: 1px dashed #b9c6d6;
    }
    .sp-inline:hover { color: #1f2937; }
    .sp-inline .sp-value { color: #1f2937; }
    .sp-inline input {
        width: 66px;
        padding: 0 2px;
        font-size: 11px;
        text-align: right;
    }
    .userid-primary { white-space: nowrap; }
    .userid-ip { margin-top: 2px; white-space: nowrap; }
    .userid-routers { margin-top: 4px; white-space: normal; font-size: 11px; }
    .customer-list-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 14px;
        overflow-x: auto;
        padding-bottom: 2px;
    }
    .expiry-quick-filters {
        display: flex;
        flex: 1 0 auto;
        gap: 10px;
        margin: 0;
    }
    .expiry-quick-filter {
        display: flex;
        flex: 1 0 150px;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 52px;
        padding: 10px 13px;
        border: 1px solid #d8dee9;
        border-radius: 8px;
        background: #ffffff;
        color: #344054;
        text-decoration: none;
        box-shadow: 0 5px 14px rgba(15, 23, 42, .06);
    }
    .expiry-quick-filter span {
        font-size: 12px;
        font-weight: 800;
    }
    .expiry-quick-filter strong {
        display: grid;
        min-width: 34px;
        height: 34px;
        place-items: center;
        border-radius: 999px;
        background: #eef3f8;
        color: #172033;
        font-size: 15px;
    }
    .expiry-quick-filter:hover,
    .expiry-quick-filter.is-active {
        border-color: #116149;
        background: #e7f7ef;
        color: #07543e;
    }
    .expiry-quick-filter.is-overdue strong {
        background: #fff1f0;
        color: #b42318;
    }
    .customer-list-toolbar .per-page-form {
        flex: 0 0 auto;
        flex-wrap: nowrap;
        margin: 0;
        white-space: nowrap;
    }
    .bulk-payment-toolbar {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 7px;
        margin: 0;
        white-space: nowrap;
    }
    .bulk-select-column { display: none; }
    .customer-bulk-selection-active .bulk-select-column { display: table-cell; }
    .customer-bulk-selection-active tr.is-bulk-selected > td { background: #e7f7ef; }
    .bulk-row-checkbox { width: 17px; height: 17px; margin: 0; accent-color: #116149; }
    td.note-cell {
        max-width: 240px;
        min-width: 120px;
        white-space: normal;
        vertical-align: top;
    }
    td.note-cell [data-inline-value] {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: 12px;
        color: #475467;
        cursor: text;
    }
    td.note-cell [data-inline-value]:empty::before {
        content: 'Add note';
        color: #98a2b3;
    }
    td.note-cell textarea { width: 100%; font: inherit; font-size: 12px; min-width: 280px; }
    /* Keep inline live-edit fields comfortably wide, even when the column
       is sized to short content like a phone number. The :has() rule lets
       the note column expand only while its editor is open. */
    td[data-inline-field] input[type="text"],
    td[data-inline-field] select {
        width: 100%;
        min-width: 220px;
        box-sizing: border-box;
        font: inherit;
        font-size: 13px;
        padding: 4px 6px;
    }
    td[data-inline-field="name"] input[type="text"] { min-width: 260px; }
    td.note-cell:has(textarea) { max-width: none; }
    .onu-sub { margin-top: 8px; padding-top: 6px; border-top: 1px dashed #dbe2ec; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 11px; line-height: 1.35; }
    .onu-sub__loc { font-weight: 700; color: #475467; }
    .onu-sub__vlan { font-weight: 700; color: #175cd3; }
    .onu-pwr { margin-top: 8px; padding-top: 6px; border-top: 1px dashed #dbe2ec; display: flex; align-items: center; gap: 14px; flex-wrap: nowrap; white-space: nowrap; font-size: 11px; line-height: 1.3; }
    .onu-pwr__row { display: inline-flex; align-items: center; gap: 5px; }
    .onu-pwr__row b { color: #667085; font-weight: 700; }
    .onu-pwr .badge { font-size: 10px; padding: 1px 6px; font-weight: 700; justify-self: start; font-variant-numeric: tabular-nums; }
    @media (max-width: 1500px) {
        .customer-filter-form {
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }
    }
</style>

<div class="topbar">
    <div><h1>{{ $showDeletedCustomers ? 'Deleted Parties' : 'Customers' }}</h1><div class="muted">{{ $showDeletedCustomers ? 'Soft-deleted parties kept with all history' : 'Customers, resellers, and product-only buyers' }}</div></div>
    <div class="actions" style="gap:8px">
        @if (! $showDeletedCustomers)
            <a class="btn" href="{{ route('customers.create') }}">Add Party</a>
        @else
            <a class="btn" href="{{ route('customers.index') }}">Active Parties</a>
        @endif
    </div>
</div>

<form method="get" class="card customer-filter-form" style="margin-bottom:16px">
    <div><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Search by name, phone, connection ID, MikroTik username"></div>
    <div><label>Role</label><select name="role"><option value="">All roles</option><option value="customer" @selected(request('role') === 'customer')>Customer</option><option value="reseller" @selected(request('role') === 'reseller')>Reseller</option></select></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div><label>Package</label><select name="package_id"><option value="">All packages</option>@foreach($packages as $package)<option value="{{ $package->id }}" @selected((int) request('package_id') === $package->id)>{{ $package->name }}</option>@endforeach</select></div>
    <div><label>Balance</label><select name="due_state"><option value="">All balances</option><option value="due" @selected(request('due_state') === 'due')>Has due</option><option value="advance" @selected(request('due_state') === 'advance')>Has advance</option></select></div>
    <div><label>Expiring Until Date</label><input type="date" name="expiring_date" min="{{ now()->toDateString() }}" value="{{ request('expiring_date') }}"></div>
    <div><label>Expired Since Date</label><input type="date" name="expired_date" max="{{ now()->toDateString() }}" value="{{ request('expired_date') }}"></div>
    <div class="filter-actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route($showDeletedCustomers ? 'customers.deleted' : 'customers.index') }}">Reset</a></div>
</form>

<div class="customer-list-toolbar">
    @if (! $showDeletedCustomers)
        <nav class="expiry-quick-filters" aria-label="Quick expiry filters">
            <a class="expiry-quick-filter is-overdue {{ request('expiry_window') === 'expired_last_7' ? 'is-active' : '' }}" href="{{ route('customers.index', ['expiry_window' => 'expired_last_7', 'per_page' => request('per_page')]) }}">
                <span>Expired Last 7 Days</span>
                <strong>{{ $expirySummary['expired_last_7'] }}</strong>
            </a>
            <a class="expiry-quick-filter {{ request('expiry_window') === 'today' ? 'is-active' : '' }}" href="{{ route('customers.index', ['expiry_window' => 'today', 'per_page' => request('per_page')]) }}">
                <span>Expires Today</span>
                <strong>{{ $expirySummary['today'] }}</strong>
            </a>
            <a class="expiry-quick-filter {{ request('expiry_window') === 'tomorrow' ? 'is-active' : '' }}" href="{{ route('customers.index', ['expiry_window' => 'tomorrow', 'per_page' => request('per_page')]) }}">
                <span>Expires Tomorrow</span>
                <strong>{{ $expirySummary['tomorrow'] }}</strong>
            </a>
            <a class="expiry-quick-filter {{ request('expiry_window') === 'in_7_days' ? 'is-active' : '' }}" href="{{ route('customers.index', ['expiry_window' => 'in_7_days', 'per_page' => request('per_page')]) }}">
                <span>Expires In 7 Days</span>
                <strong>{{ $expirySummary['in_7_days'] }}</strong>
            </a>
        </nav>
    @endif

    @if (! $showDeletedCustomers)
        <form method="post" action="{{ route('customers.bulk-payments.select') }}" class="bulk-payment-toolbar" id="bulkPaymentSelectionForm">
            @csrf
            <button class="btn secondary" type="button" id="bulkPaymentToggle">Bulk Payment</button>
            <button class="btn light" type="button" id="bulkSelectAll" hidden>Select All</button>
            <button class="btn light" type="button" id="bulkDeselectAll" hidden>Deselect All</button>
        </form>
    @endif

    @include('partials.per_page')
</div>

<table class="customer-table">
    <thead><tr>@if(! $showDeletedCustomers)<th class="bulk-select-column"><input class="bulk-row-checkbox" type="checkbox" id="bulkHeaderCheckbox" aria-label="Select all parties"></th>@endif<th>#</th><th>Name</th><th>Phone</th><th class="col-center">Role</th><th>MikroTik ID : IP</th><th class="col-center">Package</th><th class="col-center">Balance</th><th class="col-center">Status</th><th class="col-center">Active Until</th><th>Note</th>@if($showDeletedCustomers)<th>Deleted At</th>@endif<th></th></tr></thead>
    <tbody>
    @forelse ($customers as $customer)
        @php
            $netBalance = (float) $customer->account_balance - (float) ($customer->total_due_amount ?? 0);
            $activeUntil = $customer->never_suspend ? null : $customer->activeUntil();
            $daysRemaining = $customer->never_suspend ? null : $customer->activeDaysRemaining();
            $assignedSubscription = $customer->activeSubscription ?: $customer->latestSubscription;
            $inactiveSince = $activeUntil && $daysRemaining < 0
                ? $activeUntil->copy()->startOfDay()
                : $customer->updated_at?->copy()->startOfDay();
            $inactiveDays = $inactiveSince
                ? (int) $inactiveSince->diffInDays(now()->startOfDay())
                : null;
            $overdueActive = ! $customer->never_suspend && $customer->status === 'active' && $daysRemaining !== null && $daysRemaining < 0;
        @endphp
        <tr class="{{ $customer->never_suspend ? 'customer-row-special' : ($overdueActive ? 'customer-row-overdue' : '') }}" data-href="{{ route('customers.show', $customer) }}">
            @if (! $showDeletedCustomers)
                <td class="bulk-select-column">
                    <input class="bulk-row-checkbox" type="checkbox" name="customer_ids[]" value="{{ $customer->id }}" form="bulkPaymentSelectionForm" aria-label="Select {{ $customer->name }}">
                </td>
            @endif
            <td>{{ $customers->firstItem() + $loop->index }}</td>
            @php
                $onu = $customer->matched_onu ?? null;
                $onuVlan = $customer->matched_onu_vlan ?? null;
                $rx = $onu?->rx_power_dbm;
                $tx = $onu?->tx_power_dbm;
                $rxClass = $rx === null ? 'inactive'
                    : ((float) $rx > -15 ? 'partial' : ((float) $rx >= -25 ? 'active' : 'failed'));
                $txClass = $tx === null ? 'inactive'
                    : (((float) $tx <= 0.5 || (float) $tx >= 7) ? 'failed' : 'active');
            @endphp
            <td data-inline-field="name" data-inline-url="{{ route('customers.inline-update', $customer) }}">
                <span data-inline-value>{{ $customer->name }}</span>
                @if ($onu)
                    <div class="onu-sub">
                        <span class="onu-sub__loc">{{ $onu->olt_name ?: 'OLT' }} <span class="muted">·</span> {{ $onu->pon_port }}/{{ $onu->onu_id }}</span>
                        @if ($onuVlan)
                            <span class="onu-sub__vlan">VLAN {{ $onuVlan }}</span>
                        @endif
                    </div>
                @endif
            </td>
            <td data-inline-field="phone" data-inline-url="{{ route('customers.inline-update', $customer) }}">
                <span data-inline-value>{{ $customer->phone }}</span>
                @if ($onu && ($rx !== null || $tx !== null))
                    <div class="onu-pwr">
                        <span class="onu-pwr__row"><b>Rx</b><span class="badge {{ $rxClass }}">{{ $rx !== null ? number_format((float) $rx, 2) : '—' }}</span></span>
                        <span class="onu-pwr__row"><b>Tx</b><span class="badge {{ $txClass }}">{{ $tx !== null ? number_format((float) $tx, 2) : '—' }}</span></span>
                    </div>
                @endif
            </td>
            <td class="col-center">
                @if ($customer->never_suspend)
                    <span class="badge special">Special</span>
                @else
                    @if ($customer->is_customer)<span class="badge active">Customer</span>@endif
                    @if ($customer->is_vendor)<span class="badge pending">Vendor</span>@endif
                    @if ($customer->is_reseller)<span class="badge active">Reseller</span>@endif
                @endif
                @if ($maySpecial && ! $showDeletedCustomers)
                    <form method="post" action="{{ route('customers.toggle-special', $customer) }}" class="special-toggle-form"
                          onsubmit="return confirm('{{ $customer->never_suspend ? 'Remove the Special ISP flag from' : 'Make Special ISP (never suspend):' }} {{ addslashes($customer->name) }}?')">
                        @csrf
                        <button type="submit" class="special-toggle-btn {{ $customer->never_suspend ? 'is-on' : '' }}">
                            {{ $customer->never_suspend ? 'Unset' : 'Set Special' }}
                        </button>
                    </form>
                @endif
            </td>
            @php
                $hasConnection = $customer->mikrotik_username || $customer->connection_id;
                $mikrotikId = $customer->mikrotik_username ?: $customer->connection_id;
                $displayIp = $customer->use_fixed_ip
                    ? $customer->fixed_ip_address
                    : ($customer->last_connected_ip ?: $customer->learned_ip_address);
                $importedRouterNames = $customer->relationLoaded('importedSecrets')
                    ? $customer->importedSecrets->pluck('router.name')->filter()->unique()->values()
                    : collect();
                $assignedRouterNames = $customer->mikrotikRouters->pluck('name')->filter()->unique()->values();
                if ($assignedRouterNames->isEmpty() && $customer->mikrotikRouter) {
                    $assignedRouterNames = collect([$customer->mikrotikRouter->name]);
                }
                // Show every router this party touches — detected (imported secret)
                // and assigned targets combined.
                $routerNames = $importedRouterNames->merge($assignedRouterNames)->filter()->unique()->values();
            @endphp
            <td class="userid-cell">
                @if ($hasConnection)
                    <div class="userid-primary"><strong>{{ $mikrotikId }}</strong></div>
                    <div class="userid-ip"><code>{{ $displayIp ?: '—' }}</code></div>
                    <div class="muted userid-routers">{{ $routerNames->isNotEmpty() ? $routerNames->implode(', ') : 'Not detected' }}</div>
                @else
                    Product-only
                @endif
            </td>
            @php
                $currentPackageName = $assignedSubscription?->package?->name ?: 'No package';
                $listPrice = (float) ($assignedSubscription?->package?->monthly_price ?? 0);
                $effectivePrice = $assignedSubscription ? $assignedSubscription->effectivePrice() : 0.0;
                $hasSpecialPrice = (bool) $assignedSubscription?->hasCustomPrice();
                $fmtPrice = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
            @endphp
            <td class="col-center" data-inline-field="package" data-inline-url="{{ route('customers.inline-update', $customer) }}" data-package-id="{{ $assignedSubscription?->internet_package_id }}">
                <span data-inline-value>{{ $currentPackageName }}</span>
                @if ($assignedSubscription?->package)
                    <div class="pkg-price {{ $hasSpecialPrice ? 'has-sp' : '' }}">
                        <span class="pkg-price-list">৳ {{ $fmtPrice($listPrice) }}</span>
                        <span class="pkg-price-sp">৳ <span data-sp-effective>{{ $fmtPrice($effectivePrice) }}</span></span>
                        @if ($maySetSpecialPrice)
                            <span class="sp-inline" data-sp-url="{{ route('customers.special-price', $customer) }}"
                                  data-list-price="{{ $fmtPrice($listPrice) }}"
                                  title="Double-click to set this party's special price. Clear the box to remove it.">
                                SP <span class="sp-value" data-sp-value>{{ $hasSpecialPrice ? $fmtPrice($effectivePrice) : '—' }}</span>
                            </span>
                        @endif
                    </div>
                @endif
            </td>
            <td class="col-center">
                @if ($showDeletedCustomers)
                    <span class="badge {{ $netBalance < 0 ? 'due' : 'active' }}">{{ number_format($netBalance, 2) }}</span>
                @else
                    <a href="{{ route('accounting.ledger', ['customer_id' => $customer->id]) }}" title="Open this party's ledger">
                        <span class="badge {{ $netBalance < 0 ? 'due' : 'active' }}">{{ number_format($netBalance, 2) }}</span>
                    </a>
                @endif
            </td>
            <td class="col-center">
                @if ($showDeletedCustomers)
                    <span class="muted">Deleted</span>
                @else
                    <span class="badge {{ $customer->status }}">{{ $customer->status }}</span>
                    @if ($overdueActive)
                        <div><span class="badge overdue" title="Paid validity expired {{ abs($daysRemaining) }} day(s) ago — pending auto-disable / MikroTik sync">&#9888; overdue</span></div>
                    @endif
                    @if ($customer->status === 'inactive' && $inactiveSince)
                        <div class="muted">{{ $inactiveDays }} days</div>
                    @endif
                    @if ($customer->hasActiveGracePeriod())
                        <span class="badge pending">Grace</span>
                    @endif
                    @php
                        $payAmount = $effectivePrice > 0 ? number_format((float) $effectivePrice, 2, '.', '') : null;
                        $payUrl = $payAmount
                            ? route('customers.payments.create', ['customer' => $customer, 'amount' => $payAmount])
                            : route('customers.payments.create', $customer);
                    @endphp
                    <div class="customer-pay-line">
                        <a class="btn secondary customer-pay-btn" href="{{ $payUrl }}" title="Record a payment for this party">
                            <span>Pay</span>
                            @if ($payAmount)
                                <span class="customer-pay-amt">৳{{ $fmtPrice($effectivePrice) }}</span>
                            @endif
                        </a>
                    </div>
                @endif
            </td>
            <td class="col-center">
                @if ($showDeletedCustomers)
                    <span class="muted">Deleted</span>
                @else
                @if ($customer->never_suspend)
                    <span class="muted">no limit</span>
                @elseif ($customer->status === 'active' && $activeUntil)
                    <strong>{{ $activeUntil->format('d/m/Y') }}</strong>
                    @if ($daysRemaining > 0)
                        <div class="muted">{{ $daysRemaining }}d left</div>
                    @elseif ($daysRemaining === 0)
                        <div class="muted">last day</div>
                    @else
                        <div><span class="badge overdue">expired {{ abs($daysRemaining) }}d</span></div>
                    @endif
                    @if ($daysRemaining < 0 && ! $customer->grace_used_at && $mayGrantGrace)
                        @if ($customer->subscriptions_exists)
                            <form method="post" action="{{ route('customers.grace-period', $customer) }}" class="actions" style="gap:6px;margin-top:6px">
                                @csrf
                                <input type="number" name="grace_days" min="1" max="365" placeholder="Days" style="width:78px" required>
                                <button class="btn secondary" type="submit">Grace</button>
                            </form>
                        @else
                            <a class="btn light" style="margin-top:6px" href="{{ route('customers.edit', $customer) }}">Assign package for grace</a>
                        @endif
                    @endif
                @elseif ($customer->subscriptions_exists && $activeUntil === null)
                    <span class="muted">No paid month</span>
                    @if ($customer->grace_used_at)
                        <div class="muted" style="font-size:12px;">Grace already used</div>
                    @endif
                    @if (! $customer->grace_used_at && $mayGrantGrace)
                        <form method="post" action="{{ route('customers.grace-period', $customer) }}" class="actions" style="gap:6px;margin-top:6px">
                            @csrf
                            <input type="number" name="grace_days" min="1" max="365" placeholder="Days" style="width:78px" required>
                            <button class="btn secondary" type="submit">Grace</button>
                        </form>
                    @endif
                @elseif ($customer->subscriptions_exists)
                    @if (! $customer->grace_used_at && $mayGrantGrace)
                        <form method="post" action="{{ route('customers.grace-period', $customer) }}" class="actions" style="gap:6px">
                            @csrf
                            <input type="number" name="grace_days" min="1" max="365" placeholder="Days" style="width:78px" required>
                            <button class="btn secondary" type="submit">Grace</button>
                        </form>
                    @elseif ($customer->grace_used_at)
                        <a class="btn light" href="{{ route('customers.edit', $customer) }}">Assign package for grace</a>
                    @endif
                @else
                    <span class="muted">Grace used {{ $customer->grace_until?->format('d/m/Y') }}</span>
                @endif
                @endif
            </td>
            <td class="note-cell" data-inline-field="notes" data-inline-url="{{ route('customers.inline-update', $customer) }}" title="Double-click to edit this note">
                <span data-inline-value>{{ $customer->notes }}</span>
            </td>
            @if ($showDeletedCustomers)
                <td>{{ optional($customer->deleted_at)->format('d/m/Y H:i') }}</td>
            @endif
            <td class="customer-row-actions">
            @if ($showDeletedCustomers)
                    <form method="post" action="{{ route('customers.restore', $customer->id) }}" onsubmit="return confirm('Restore this party to active list?');" style="display:inline">
                        @csrf
                        <button class="btn secondary" type="submit">Restore</button>
                    </form>
                    <a class="btn light" href="{{ route('customers.show', $customer) }}">View</a>
                    <a class="btn light" href="{{ route('customers.deleted.history', $customer->id) }}">Change Log</a>
                @else
                    <details class="customer-action-menu">
                        <summary class="btn light">Actions</summary>
                        <div class="customer-action-menu-list">
                            <a href="{{ route('accounting.ledger', ['customer_id' => $customer->id]) }}">Ledger</a>
                            <a href="{{ route('customers.show', $customer) }}">View</a>
                            <a href="{{ route('customers.edit', $customer) }}">Edit</a>
                            <form method="post" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this party, disconnect its active session, remove its MikroTik secret, and keep all App history?');">
                                @csrf
                                @method('delete')
                                <button class="customer-action-delete" type="submit">Delete</button>
                            </form>
                        </div>
                    </details>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="13">No parties found.</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $customers->links() }}</div>

<script>
const customerCsrfToken = document.querySelector('meta[name="csrf-token"]').content;
const customerPackages = @json(($packages ?? collect())->mapWithKeys(fn ($package) => [$package->id => $package->name])->toArray());

function editCustomerInlineCell(cell, event) {
    if (cell.querySelector('input, select, textarea')) return;
    const valueNode = cell.querySelector('[data-inline-value]');
    const field = cell.dataset.inlineField;
    const originalValue = valueNode ? valueNode.textContent.trim() : '';

    if (field === 'package') {
        const select = document.createElement('select');
        const option = new Option('No package', '');
        option.selected = !(cell.dataset.packageId || cell.getAttribute('data-package-id'));
        select.append(option);

        Object.entries(customerPackages).forEach(([id, packageName]) => {
            const packageOption = new Option(packageName, id, false, String((cell.dataset.packageId || '').trim()) === String(id));
            select.append(packageOption);
        });

        select.style.width = '100%';
        cell.replaceChildren(select);
        select.focus();

        select.dataset.value = cell.dataset.packageId || '';
        let saving = false;

        const restoreCell = () => {
            cell.innerHTML = '';
            cell.append(valueNode);
        };

        const savePackage = async () => {
            if (saving) return;
            saving = true;
            select.disabled = true;
            try {
                const response = await fetch(cell.dataset.inlineUrl, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': customerCsrfToken,
                    },
                    body: JSON.stringify({ field, value: select.value }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Could not update package');
                valueNode.textContent = data.value;
                cell.dataset.packageId = data.package_id ? String(data.package_id) : '';
            } catch (error) {
                alert(error.message);
            } finally {
                saving = false;
                select.disabled = false;
                restoreCell();
            }
        };

        select.addEventListener('blur', savePackage);
        select.addEventListener('change', savePackage);
        select.addEventListener('keydown', function (keyEvent) {
            if (keyEvent.key === 'Escape') {
                restoreCell();
            }
        });

        return;
    }

    const isNote = field === 'notes';
    const input = document.createElement(isNote ? 'textarea' : 'input');
    if (!isNote) input.type = 'text';
    if (isNote) input.rows = 3;
    input.value = originalValue;
    input.style.width = '100%';

    // Keep any sibling nodes in the cell (e.g. the OLT/ONU sub-line under a
    // name) so an inline edit doesn't drop them until the next reload.
    const extraNodes = [...cell.childNodes].filter((node) => node !== valueNode);

    cell.replaceChildren(input);
    input.focus();
    if (!isNote) input.select();

    let saving = false;
    const restoreCell = () => {
        cell.replaceChildren(valueNode, ...extraNodes);
    };
    const saveField = async () => {
        if (saving) return;
        saving = true;
        input.disabled = true;
        try {
            const response = await fetch(cell.dataset.inlineUrl, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': customerCsrfToken,
                },
                body: JSON.stringify({ field, value: input.value }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not update');
            valueNode.textContent = data.value;
        } catch (error) {
            alert(error.message);
        } finally {
            saving = false;
            input.disabled = false;
            restoreCell();
        }
    };

    input.addEventListener('blur', saveField);
    input.addEventListener('keydown', function (keyEvent) {
        // Plain Enter saves a single-line field; in the note textarea it adds a
        // newline and Ctrl+Enter (or blur) saves instead.
        if (keyEvent.key === 'Enter' && (!isNote || keyEvent.ctrlKey)) {
            keyEvent.preventDefault();
            saveField();
        }
        if (keyEvent.key === 'Escape') {
            restoreCell();
        }
    });
}

document.querySelectorAll('[data-inline-field="name"], [data-inline-field="phone"], [data-inline-field="notes"]').forEach((cell) => {
    cell.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
    });
    cell.addEventListener('dblclick', function (event) {
        event.preventDefault();
        event.stopPropagation();
        editCustomerInlineCell(this);
    });
});

document.querySelectorAll('td[data-inline-field="package"]').forEach((cell) => {
    cell.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
    });
    cell.addEventListener('dblclick', function (event) {
        event.preventDefault();
        event.stopPropagation();
        editCustomerInlineCell(this);
    });
});

// Inline "SP" special-price chip inside the Package cell: double-click to edit,
// no button, saves on Enter/blur, clear the box to remove the special price.
document.querySelectorAll('.sp-inline').forEach((chip) => {
    chip.addEventListener('click', (event) => event.stopPropagation());

    chip.addEventListener('dblclick', function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (chip.querySelector('input')) return;

        const valueNode = chip.querySelector('[data-sp-value]');
        const current = valueNode ? valueNode.textContent.trim().replace(/,/g, '') : '';
        const originalHtml = chip.innerHTML;
        let finished = false;

        const input = document.createElement('input');
        input.type = 'text';
        input.inputMode = 'numeric';
        input.value = (current && current !== '—') ? current : '';
        input.placeholder = chip.dataset.listPrice || '';

        chip.textContent = 'SP ';
        chip.appendChild(input);
        input.focus();
        input.select();

        const cancel = () => { if (!finished) { finished = true; chip.innerHTML = originalHtml; } };

        const applyResult = (data) => {
            finished = true;
            const hasSp = !!data.has_special;
            chip.innerHTML = 'SP <span class="sp-value" data-sp-value>' + (hasSp ? data.special_price_formatted : '—') + '</span>';
            const priceWrap = chip.closest('.pkg-price');
            if (priceWrap) {
                priceWrap.classList.toggle('has-sp', hasSp);
                const eff = priceWrap.querySelector('[data-sp-effective]');
                if (eff && hasSp) eff.textContent = data.special_price_formatted;
            }
        };

        const save = async () => {
            if (finished) return;
            finished = true;
            input.disabled = true;
            const raw = input.value.trim();
            try {
                const response = await fetch(chip.dataset.spUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': customerCsrfToken,
                    },
                    body: JSON.stringify({ custom_price: raw === '' ? null : raw }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Could not save the special price.');
                applyResult(data);
            } catch (error) {
                alert(error.message);
                chip.innerHTML = originalHtml;
            }
        };

        input.addEventListener('blur', save);
        input.addEventListener('keydown', function (keyEvent) {
            if (keyEvent.key === 'Enter') { keyEvent.preventDefault(); save(); }
            if (keyEvent.key === 'Escape') { keyEvent.preventDefault(); cancel(); }
        });
    });
});

document.querySelectorAll('.customer-action-menu').forEach((menu) => {
    menu.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    menu.addEventListener('toggle', function () {
        if (! menu.open) return;
        document.querySelectorAll('.customer-action-menu[open]').forEach((otherMenu) => {
            if (otherMenu !== menu) otherMenu.removeAttribute('open');
        });
    });
});

const bulkPaymentToggle = document.getElementById('bulkPaymentToggle');
const bulkSelectAll = document.getElementById('bulkSelectAll');
const bulkDeselectAll = document.getElementById('bulkDeselectAll');
const bulkHeaderCheckbox = document.getElementById('bulkHeaderCheckbox');
const bulkSelectionForm = document.getElementById('bulkPaymentSelectionForm');
const bulkRowCheckboxes = [...document.querySelectorAll('.bulk-select-column input[name="customer_ids[]"]')];
let bulkSelectionActive = false;

function updateBulkSelectionUi() {
    const selected = bulkRowCheckboxes.filter((checkbox) => checkbox.checked);
    bulkPaymentToggle.textContent = bulkSelectionActive ? `Show Bulk Payment (${selected.length})` : 'Bulk Payment';
    bulkSelectAll.hidden = ! bulkSelectionActive;
    bulkDeselectAll.hidden = ! bulkSelectionActive;
    if (bulkHeaderCheckbox) {
        bulkHeaderCheckbox.checked = selected.length > 0 && selected.length === bulkRowCheckboxes.length;
        bulkHeaderCheckbox.indeterminate = selected.length > 0 && selected.length < bulkRowCheckboxes.length;
    }
    bulkRowCheckboxes.forEach((checkbox) => checkbox.closest('tr')?.classList.toggle('is-bulk-selected', checkbox.checked));
}

bulkPaymentToggle?.addEventListener('click', function () {
    if (! bulkSelectionActive) {
        bulkSelectionActive = true;
        document.body.classList.add('customer-bulk-selection-active');
        updateBulkSelectionUi();
        return;
    }

    if (! bulkRowCheckboxes.some((checkbox) => checkbox.checked)) {
        alert('Select at least one party for bulk payment.');
        return;
    }

    bulkSelectionForm.requestSubmit();
});

bulkSelectAll?.addEventListener('click', function () {
    bulkRowCheckboxes.forEach((checkbox) => { checkbox.checked = true; });
    updateBulkSelectionUi();
});

bulkDeselectAll?.addEventListener('click', function () {
    bulkRowCheckboxes.forEach((checkbox) => { checkbox.checked = false; });
    updateBulkSelectionUi();
});

bulkHeaderCheckbox?.addEventListener('change', function () {
    bulkRowCheckboxes.forEach((checkbox) => { checkbox.checked = bulkHeaderCheckbox.checked; });
    updateBulkSelectionUi();
});

bulkRowCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('click', function (event) {
        event.stopPropagation();
    });
    checkbox.addEventListener('change', updateBulkSelectionUi);
});
</script>
@endsection
