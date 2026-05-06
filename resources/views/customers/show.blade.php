@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>{{ $customer->name }}</h1><div class="muted">{{ $customer->connection_id }} · {{ $customer->phone }}</div></div>
    <div class="actions">
        <a class="btn" href="{{ route('customers.payments.create', $customer) }}">Record Payment</a>
        <a class="btn secondary" href="{{ route('customers.edit', $customer) }}">Edit</a>
        <a class="btn light" href="{{ route('customers.index') }}">Back</a>
    </div>
</div>

<div class="grid two">
    <section class="card">
        <h2>Profile</h2>
        @php
            $activeUntil = $customer->activeUntil();
            $daysRemaining = $customer->activeDaysRemaining();
        @endphp
        <p><strong>Status:</strong> <span class="badge {{ $customer->status }}">{{ $customer->status }}</span></p>
        <p>
            <strong>Active Until:</strong>
            @if ($customer->status === 'active' && $activeUntil)
                {{ $activeUntil->format('Y-m-d') }}
                <span class="muted">({{ $daysRemaining === 0 ? 'last day' : $daysRemaining.' days left' }})</span>
                @if ($customer->hasActiveGracePeriod())
                    <span class="badge pending">Grace period</span>
                @endif
            @elseif ($customer->status === 'active')
                <span class="muted">No paid service month found</span>
            @else
                <span class="muted">Inactive</span>
            @endif
        </p>
        <p><strong>Grace Period:</strong>
            @if ($customer->grace_used_at)
                Used {{ $customer->grace_days }} day(s), until {{ $customer->grace_until?->format('Y-m-d') }}
            @else
                Not used
            @endif
        </p>
        @if ($customer->status === 'inactive' && ! $customer->grace_used_at)
            <form method="post" action="{{ route('customers.grace-period', $customer) }}" class="actions" style="margin:10px 0">
                @csrf
                <input type="number" name="grace_days" min="1" max="365" placeholder="Grace days" required>
                <button class="btn secondary" type="submit">Give Grace Period</button>
            </form>
        @endif
        <p><strong>Special Customer:</strong> {{ $customer->never_suspend ? 'Yes - never close line and auto-generate bill' : 'No' }}</p>
        <p><strong>Email:</strong> {{ $customer->email ?? 'Not provided' }}</p>
        <p><strong>Address:</strong> {{ $customer->address }}</p>
        <p><strong>Package:</strong> {{ $customer->activeSubscription?->package?->name ?? 'No active package' }}</p>
        <p><strong>MikroTik User ID:</strong> {{ $customer->mikrotik_username ?? $customer->connection_id }}</p>
        <p><strong>MikroTik Password:</strong> 4321</p>
        <p><strong>MikroTik Target:</strong> {{ $customer->mikrotikRouter ? $customer->mikrotikRouter->name.' - '.$customer->mikrotikRouter->ip_address.':'.$customer->mikrotikRouter->api_port : 'All active MikroTik routers' }}</p>
        <p><strong>MikroTik Profile:</strong> {{ $customer->activeSubscription?->package?->mikrotik_profile ?? 'No active profile' }}</p>
    </section>
    <section class="card">
        <h2>Billing Summary</h2>
        @php
            $totalDue = (float) $customer->invoices->sum('due_amount');
            $netBalance = (float) $customer->account_balance - $totalDue;
        @endphp
        <p><strong>Total invoices:</strong> {{ $customer->invoices->count() }}</p>
        <p><strong>Total due:</strong> {{ number_format($totalDue, 2) }}</p>
        <p><strong>Advance Balance:</strong> {{ number_format($customer->account_balance, 2) }}</p>
        <p><strong>Net Balance:</strong> <span class="badge {{ $netBalance < 0 ? 'due' : 'active' }}">{{ number_format($netBalance, 2) }}</span></p>
    </section>
</div>

<section class="card" style="margin-top:16px">
    <h2>Invoices</h2>
    <table>
        <thead><tr><th>Invoice</th><th>Month</th><th>Total</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($customer->invoices as $invoice)
            <tr>
                <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a></td>
                <td>{{ $invoice->billing_month }}</td>
                <td>{{ number_format($invoice->total, 2) }}</td>
                <td>{{ number_format($invoice->due_amount, 2) }}</td>
                <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5">No invoices yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
