@extends('layouts.app')

@section('content')
@php
    $canOpenPackages = auth()->user()?->hasPermission('manage_packages');
@endphp
<div class="topbar">
    <div><h1>Parties</h1><div class="muted">Customers, vendors, resellers, and product-only buyers</div></div>
    <a class="btn" href="{{ route('customers.create') }}">Add Party</a>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Search by name, phone, connection ID, MikroTik username"></div>
    <div><label>Role</label><select name="role"><option value="">All roles</option><option value="customer" @selected(request('role') === 'customer')>Customer</option><option value="vendor" @selected(request('role') === 'vendor')>Vendor</option><option value="reseller" @selected(request('role') === 'reseller')>Reseller</option></select></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div><label>Package</label><select name="package_id"><option value="">All packages</option>@foreach($packages as $package)<option value="{{ $package->id }}" @selected((int) request('package_id') === $package->id)>{{ $package->name }}</option>@endforeach</select></div>
    <div><label>Balance</label><select name="due_state"><option value="">All balances</option><option value="due" @selected(request('due_state') === 'due')>Has due</option><option value="advance" @selected(request('due_state') === 'advance')>Has advance</option></select></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('customers.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Role</th><th>User ID</th><th>Package</th><th>Balance</th><th>Status</th><th>Active Until</th><th></th></tr></thead>
    <tbody>
    @forelse ($customers as $customer)
        @php
            $netBalance = (float) $customer->account_balance - (float) ($customer->total_due_amount ?? 0);
            $activeUntil = $customer->activeUntil();
            $daysRemaining = $customer->activeDaysRemaining();
            $nextActiveDate = now()->addMonthNoOverflow()->toDateString();
        @endphp
        <tr class="{{ $customer->never_suspend ? 'customer-row-special' : '' }}" data-href="{{ route('customers.show', $customer) }}">
            <td>{{ $customers->firstItem() + $loop->index }}</td>
            <td><a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a></td>
            <td>{{ $customer->phone }}</td>
            <td>
                @if ($customer->is_customer)<span class="badge active">Customer</span>@endif
                @if ($customer->is_vendor)<span class="badge pending">Vendor</span>@endif
                @if ($customer->is_reseller)<span class="badge active">Reseller</span>@endif
                @if ($customer->never_suspend)
                    <span class="badge special">Special ISP</span>
                @endif
            </td>
            <td>{{ $customer->mikrotik_username ?? $customer->connection_id ?? 'Product-only' }}</td>
            <td>
                @if ($customer->activeSubscription?->package)
                    @if ($canOpenPackages)
                        <a href="{{ route('packages.show', $customer->activeSubscription->package) }}">{{ $customer->activeSubscription->package->name }}</a>
                    @else
                        {{ $customer->activeSubscription->package->name }}
                    @endif
                @else
                    No package
                @endif
            </td>
            <td>
                <span class="badge {{ $netBalance < 0 ? 'due' : 'active' }}">{{ number_format($netBalance, 2) }}</span>
            </td>
            <td>
                <span class="badge {{ $customer->status }}">{{ $customer->status }}</span>
                @if ($customer->hasActiveGracePeriod())
                    <span class="badge pending">Grace</span>
                @endif
            </td>
            <td>
                @if ($customer->status === 'active' && $activeUntil)
                    <strong>{{ $activeUntil->format('Y-m-d') }}</strong>
                    @if ($daysRemaining > 0)
                        <div class="muted">{{ $daysRemaining }} days left</div>
                    @elseif ($daysRemaining === 0)
                        <div class="muted">Last day</div>
                    @else
                        <div><span class="badge overdue">Expired {{ abs($daysRemaining) }} days ago</span></div>
                    @endif
                    @if ($daysRemaining < 0 && ! $customer->grace_used_at)
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
                @elseif ($customer->status === 'active')
                    <span class="muted">No paid month</span>
                    @if ($customer->subscriptions_exists)
                        <form method="post" action="{{ route('customers.activate-next-date', $customer) }}" class="actions" style="gap:6px;margin-top:6px">
                            @csrf
                            <button class="btn secondary" type="submit">Activate until {{ $nextActiveDate }}</button>
                        </form>
                    @else
                        <a class="btn light" style="margin-top:6px" href="{{ route('customers.edit', $customer) }}">Assign package for activation</a>
                    @endif
                @elseif ($customer->subscriptions_exists && $activeUntil === null)
                    <span class="muted">No paid month</span>
                    @if ($customer->grace_used_at)
                        <div class="muted" style="font-size:12px;">Grace already used</div>
                    @endif
                    <form method="post" action="{{ route('customers.activate-next-date', $customer) }}" class="actions" style="gap:6px;margin-top:6px">
                        @csrf
                        <button class="btn secondary" type="submit">Activate until {{ $nextActiveDate }}</button>
                    </form>
                @elseif ($customer->subscriptions_exists)
                    @if (! $customer->grace_used_at)
                        <form method="post" action="{{ route('customers.grace-period', $customer) }}" class="actions" style="gap:6px">
                            @csrf
                            <input type="number" name="grace_days" min="1" max="365" placeholder="Days" style="width:78px" required>
                            <button class="btn secondary" type="submit">Grace</button>
                        </form>
                    @else
                        <a class="btn light" href="{{ route('customers.edit', $customer) }}">Assign package for grace</a>
                    @endif
                @else
                    <span class="muted">Grace used {{ $customer->grace_until?->format('Y-m-d') }}</span>
                @endif
            </td>
            <td class="actions">
                <a class="btn secondary" href="{{ route('customers.payments.create', $customer) }}">Pay</a>
                <a class="btn light" href="{{ route('accounting.ledger', ['customer_id' => $customer->id]) }}">Ledger</a>
                <a class="btn light" href="{{ route('customers.show', $customer) }}">View</a>
                <a class="btn light" href="{{ route('customers.edit', $customer) }}">Edit</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="10">No parties found.</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $customers->links() }}</div>
@endsection
