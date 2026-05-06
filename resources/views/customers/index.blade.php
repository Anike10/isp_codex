@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Customers</h1><div class="muted">Internet connection and customer profiles</div></div>
    <a class="btn" href="{{ route('customers.create') }}">Add Customer</a>
</div>

<form method="get" class="card actions" style="margin-bottom:16px">
    <input name="search" value="{{ request('search') }}" placeholder="Search by name, phone, connection ID">
    <button class="btn secondary" type="submit">Search</button>
    <a class="btn light" href="{{ route('customers.index') }}">Reset</a>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Name</th><th>Phone</th><th>User ID</th><th>Package</th><th>Balance</th><th>Status</th><th>Active Until</th><th></th></tr></thead>
    <tbody>
    @forelse ($customers as $customer)
        @php
            $netBalance = (float) $customer->account_balance - (float) ($customer->total_due_amount ?? 0);
            $activeUntil = $customer->activeUntil();
            $daysRemaining = $customer->activeDaysRemaining();
        @endphp
        <tr data-href="{{ route('customers.show', $customer) }}">
            <td>{{ $customer->name }}</td>
            <td>{{ $customer->phone }}</td>
            <td>{{ $customer->mikrotik_username ?? $customer->connection_id }}</td>
            <td>{{ $customer->activeSubscription?->package?->name ?? 'No package' }}</td>
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
                    <div class="muted">{{ $daysRemaining === 0 ? 'Last day' : $daysRemaining.' days left' }}</div>
                @elseif ($customer->status === 'active')
                    <span class="muted">No paid month</span>
                @elseif (! $customer->grace_used_at)
                    <form method="post" action="{{ route('customers.grace-period', $customer) }}" class="actions" style="gap:6px">
                        @csrf
                        <input type="number" name="grace_days" min="1" max="365" placeholder="Days" style="width:78px" required>
                        <button class="btn secondary" type="submit">Grace</button>
                    </form>
                @else
                    <span class="muted">Grace used {{ $customer->grace_until?->format('Y-m-d') }}</span>
                @endif
            </td>
            <td class="actions">
                <a class="btn secondary" href="{{ route('customers.payments.create', $customer) }}">Pay</a>
                <a class="btn light" href="{{ route('customers.show', $customer) }}">View</a>
                <a class="btn light" href="{{ route('customers.edit', $customer) }}">Edit</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="8">No customers found.</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $customers->links() }}</div>
@endsection
