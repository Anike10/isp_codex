@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
@php $showDeletedCustomers = $showDeletedCustomers ?? false; @endphp
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
    }
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
    @media (max-width: 1500px) {
        .customer-filter-form {
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }
    }
</style>

<div class="topbar">
    <div><h1>{{ $showDeletedCustomers ? 'Deleted Parties' : 'Parties' }}</h1><div class="muted">{{ $showDeletedCustomers ? 'Soft-deleted parties kept with all history' : 'Customers, vendors, resellers, and product-only buyers' }}</div></div>
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
    <div><label>Role</label><select name="role"><option value="">All roles</option><option value="customer" @selected(request('role') === 'customer')>Customer</option><option value="vendor" @selected(request('role') === 'vendor')>Vendor</option><option value="reseller" @selected(request('role') === 'reseller')>Reseller</option></select></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div><label>Package</label><select name="package_id"><option value="">All packages</option>@foreach($packages as $package)<option value="{{ $package->id }}" @selected((int) request('package_id') === $package->id)>{{ $package->name }}</option>@endforeach</select></div>
    <div><label>Balance</label><select name="due_state"><option value="">All balances</option><option value="due" @selected(request('due_state') === 'due')>Has due</option><option value="advance" @selected(request('due_state') === 'advance')>Has advance</option></select></div>
    <div><label>Expiring Date</label><input type="date" name="expiring_date" value="{{ request()->has('expiring_date') ? request('expiring_date') : now()->addDay()->toDateString() }}"></div>
    <div><label>Expired Date</label><input type="date" name="expired_date" value="{{ request()->has('expired_date') ? request('expired_date') : now()->subDay()->toDateString() }}"></div>
    <div class="filter-actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route($showDeletedCustomers ? 'customers.deleted' : 'customers.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Role</th><th>User ID</th><th>Package</th><th>Balance</th><th>Status</th><th>Active Until</th>@if($showDeletedCustomers)<th>Deleted At</th>@endif<th></th></tr></thead>
    <tbody>
    @forelse ($customers as $customer)
        @php
            $netBalance = (float) $customer->account_balance - (float) ($customer->total_due_amount ?? 0);
            $activeUntil = $customer->activeUntil();
            $daysRemaining = $customer->activeDaysRemaining();
            $nextActiveDate = now()->addMonthNoOverflow()->toDateString();
            $canQuickActivate = ($hasImportedSecretTable ?? false) && ($customer->imported_secret_exists ?? false) && ! ($customer->invoices_exists ?? false);
        @endphp
        <tr class="{{ $customer->never_suspend ? 'customer-row-special' : '' }}" @if(! $showDeletedCustomers) data-href="{{ route('customers.show', $customer) }}" @endif>
            <td>{{ $customers->firstItem() + $loop->index }}</td>
            <td @if(! $showDeletedCustomers) data-inline-field="name" data-inline-url="{{ route('customers.inline-update', $customer) }}" @endif>
                <span data-inline-value>{{ $customer->name }}</span>
            </td>
            <td @if(! $showDeletedCustomers) data-inline-field="phone" data-inline-url="{{ route('customers.inline-update', $customer) }}" @endif>
                <span data-inline-value>{{ $customer->phone }}</span>
            </td>
            <td>
                @if ($customer->is_customer)<span class="badge active">Customer</span>@endif
                @if ($customer->is_vendor)<span class="badge pending">Vendor</span>@endif
                @if ($customer->is_reseller)<span class="badge active">Reseller</span>@endif
                @if ($customer->never_suspend)
                    <span class="badge special">Special ISP</span>
                @endif
            </td>
            <td>{{ $customer->mikrotik_username ?? $customer->connection_id ?? 'Product-only' }}</td>
            <td @if(! $showDeletedCustomers) data-inline-field="package" data-inline-url="{{ route('customers.inline-update', $customer) }}" @endif data-package-id="{{ $customer->activeSubscription?->internet_package_id }}">
                @php $currentPackageName = $customer->activeSubscription?->package?->name ?: 'No package'; @endphp
                <span data-inline-value>{{ $currentPackageName }}</span>
            </td>
            <td>
                <span class="badge {{ $netBalance < 0 ? 'due' : 'active' }}">{{ number_format($netBalance, 2) }}</span>
            </td>
            <td>
                @if ($showDeletedCustomers)
                    <span class="muted">Deleted</span>
                @else
                    <span class="badge {{ $customer->status }}">{{ $customer->status }}</span>
                    @if ($customer->hasActiveGracePeriod())
                        <span class="badge pending">Grace</span>
                    @endif
                @endif
            </td>
            <td>
                @if ($showDeletedCustomers)
                    <span class="muted">Deleted</span>
                @else
                @if ($customer->status === 'active' && $activeUntil)
                    <strong>{{ $activeUntil->format('d/m/Y') }}</strong>
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
                    @if ($customer->subscriptions_exists && $canQuickActivate)
                        <form method="post" action="{{ route('customers.activate-next-date', $customer) }}" class="actions" style="gap:6px;margin-top:6px">
                            @csrf
                            <input type="date" name="active_until" value="{{ $nextActiveDate }}" min="{{ now()->toDateString() }}" style="width:165px">
                            <button class="btn secondary" type="submit">Activate until</button>
                        </form>
                    @endif
                @elseif ($customer->subscriptions_exists && $activeUntil === null)
                    <span class="muted">No paid month</span>
                    @if ($customer->grace_used_at)
                        <div class="muted" style="font-size:12px;">Grace already used</div>
                    @endif
                    @if ($customer->subscriptions_exists && $canQuickActivate)
                        <form method="post" action="{{ route('customers.activate-next-date', $customer) }}" class="actions" style="gap:6px;margin-top:6px">
                            @csrf
                            <input type="date" name="active_until" value="{{ $nextActiveDate }}" min="{{ now()->toDateString() }}" style="width:165px">
                            <button class="btn secondary" type="submit">Activate until</button>
                        </form>
                    @endif
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
                    <span class="muted">Grace used {{ $customer->grace_until?->format('d/m/Y') }}</span>
                @endif
                @endif
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
                    <a class="btn light" href="{{ route('customers.deleted.history', $customer->id) }}">History</a>
                @else
                    <details class="customer-action-menu">
                        <summary class="btn light">Actions</summary>
                        <div class="customer-action-menu-list">
                            <a href="{{ route('customers.payments.create', $customer) }}">Pay</a>
                            <a href="{{ route('accounting.ledger', ['customer_id' => $customer->id]) }}">Ledger</a>
                            <a href="{{ route('customers.show', $customer) }}">View</a>
                            <a href="{{ route('customers.edit', $customer) }}">Edit</a>
                            <form method="post" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this party and keep all history?');">
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
        <tr><td colspan="{{ $showDeletedCustomers ? 11 : 10 }}">No parties found.</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $customers->links() }}</div>

@if (! $showDeletedCustomers)
<script>
const customerCsrfToken = document.querySelector('meta[name="csrf-token"]').content;
const customerPackages = @json($packages->mapWithKeys(fn ($package) => [$package->id => $package->name])->toArray());

function editCustomerInlineCell(cell, event) {
    if (cell.querySelector('input, select')) return;
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

    const input = document.createElement('input');
    input.type = 'text';
    input.value = originalValue;
    input.style.width = '100%';

    cell.replaceChildren(input);
    input.focus();
    input.select();

    let saving = false;
    const restoreCell = () => {
        cell.replaceChildren(valueNode);
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
        if (keyEvent.key === 'Enter') {
            keyEvent.preventDefault();
            saveField();
        }
        if (keyEvent.key === 'Escape') {
            restoreCell();
        }
    });
}

document.querySelectorAll('[data-inline-field="name"], [data-inline-field="phone"]').forEach((cell) => {
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
</script>
@endif
@endsection
