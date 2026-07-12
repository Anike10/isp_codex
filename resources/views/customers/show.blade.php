@extends('layouts.app')

@section('content')
@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canOpenWarrantyClaims = auth()->user()?->hasPermission('view_warranty_claims')
        || auth()->user()?->hasPermission('manage_warranty_claims')
        || auth()->user()?->hasPermission('manage_products');
@endphp
<div class="topbar">
    <div><h1>{{ $customer->name }}</h1><div class="muted">{{ $customer->connection_id ?: 'Product-only party' }} - {{ $customer->phone }}</div></div>
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
        <p><strong>Party Type:</strong>
            @if ($customer->is_customer)<span class="badge active">Customer</span>@endif
            @if ($customer->is_vendor)<span class="badge pending">Vendor</span>@endif
        </p>
        <p>
            <strong>Active Until:</strong>
            @if ($customer->status === 'active' && $activeUntil)
                {{ $activeUntil->format('Y-m-d') }}
                @if ($daysRemaining > 0)
                    <span class="muted">({{ $daysRemaining }} days left)</span>
                @elseif ($daysRemaining === 0)
                    <span class="muted">(last day)</span>
                @else
                    <span class="badge overdue">Expired {{ abs($daysRemaining) }} days ago</span>
                @endif
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
        @if (($customer->status === 'inactive' || ($daysRemaining !== null && $daysRemaining < 0)) && ! $customer->grace_used_at)
            @if ($customer->subscriptions->isNotEmpty())
                <form method="post" action="{{ route('customers.grace-period', $customer) }}" class="actions" style="margin:10px 0">
                    @csrf
                    <input type="number" name="grace_days" min="1" max="365" placeholder="Grace days" required>
                    <button class="btn secondary" type="submit">Give Grace Period</button>
                </form>
            @else
                <p><a class="btn light" href="{{ route('customers.edit', $customer) }}">Assign package before giving grace</a></p>
            @endif
        @endif
        <p><strong>Special ISP Customer:</strong> {{ $customer->never_suspend ? 'Yes - never close line and auto-generate bill' : 'No' }}</p>
        <p><strong>Email:</strong> {{ $customer->email ?? 'Not provided' }}</p>
        <p><strong>Address:</strong> {{ $customer->address }}</p>
        <p><strong>Package:</strong> {{ $customer->activeSubscription?->package?->name ?? 'No active package' }}</p>
        <p><strong>MikroTik User ID:</strong> {{ $customer->mikrotik_username ?? $customer->connection_id ?? 'Not assigned' }}</p>
        <p><strong>MikroTik Password:</strong> {{ ($customer->mikrotik_username || $customer->connection_id) ? '4321' : 'Not assigned' }}</p>
        <p><strong>MikroTik Target:</strong> {{ ($customer->mikrotik_username || $customer->connection_id) ? ($customer->mikrotikRouter ? $customer->mikrotikRouter->name.' - '.$customer->mikrotikRouter->ip_address.':'.$customer->mikrotikRouter->api_port : 'All active MikroTik routers') : 'Not assigned' }}</p>
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
    <h2>Assets & Warranty</h2>
    <table>
        <thead><tr><th>Product</th><th>Serial</th><th>Invoice</th><th>Sold Date</th><th>Warranty</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($customer->productSerials as $serial)
            @php
                $openClaim = $serial->warrantyClaims->first(fn ($claim) => in_array($claim->status, \App\Models\WarrantyClaim::OPEN_STATUSES, true));
                $warrantyLabel = $serial->warranty_until
                    ? ($serial->warranty_until->copy()->endOfDay()->gte(now()) ? 'In warranty until '.$serial->warranty_until->format('Y-m-d') : 'Expired '.$serial->warranty_until->format('Y-m-d'))
                    : 'No warranty';
            @endphp
            <tr>
                <td>{{ $serial->product?->name ?? 'N/A' }}</td>
                <td><span class="badge">{{ $serial->serial_number }}</span></td>
                <td>
                    @if ($serial->invoice)
                        @if ($canOpenInvoices)
                            <a href="{{ route('invoices.show', $serial->invoice) }}">{{ $serial->invoice->invoice_no }}</a>
                        @else
                            {{ $serial->invoice->invoice_no }}
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $serial->sold_at?->format('Y-m-d') ?? 'N/A' }}</td>
                <td>{{ $warrantyLabel }}</td>
                <td>
                    @if ($openClaim && $canOpenWarrantyClaims)
                        <a class="badge pending" href="{{ route('warranty-claims.show', $openClaim) }}">{{ str_replace('_', ' ', $openClaim->status) }}</a>
                    @elseif ($openClaim)
                        <span class="badge pending">{{ str_replace('_', ' ', $openClaim->status) }}</span>
                    @else
                        {{ str_replace('_', ' ', $serial->status) }}
                    @endif
                </td>
                <td>
                    @if (auth()->user()?->hasPermission('manage_warranty_claims') && ! $openClaim)
                        <a class="btn light" href="{{ route('warranty-claims.create', ['product_serial_id' => $serial->id]) }}">Warranty Claim</a>
                    @elseif ($openClaim && $canOpenWarrantyClaims)
                        <a class="btn light" href="{{ route('warranty-claims.show', $openClaim) }}">View Claim</a>
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No sold serial assets found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card" style="margin-top:16px">
    <h2>Warranty Claims</h2>
    <table>
        <thead><tr><th>Claim</th><th>Product</th><th>Serial</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        @forelse ($customer->warrantyClaims as $claim)
            <tr>
                <td>
                    @if ($canOpenWarrantyClaims)
                        <a href="{{ route('warranty-claims.show', $claim) }}">{{ $claim->claim_no }}</a>
                    @else
                        {{ $claim->claim_no }}
                    @endif
                </td>
                <td>{{ $claim->product?->name ?? 'Manual claim' }}</td>
                <td>{{ $claim->productSerial?->serial_number ?? 'N/A' }}</td>
                <td><span class="badge pending">{{ str_replace('_', ' ', $claim->status) }}</span></td>
                <td>{{ $claim->claim_date?->format('Y-m-d') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No warranty claims yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card" style="margin-top:16px">
    <h2>Invoices</h2>
    <table>
        <thead><tr><th>Invoice</th><th>Month</th><th>Total</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($customer->invoices as $invoice)
            <tr>
                <td>
                    @if ($canOpenInvoices)
                        <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                    @else
                        {{ $invoice->invoice_no }}
                    @endif
                </td>
                <td>{{ $invoice->formatted_billing_month }}</td>
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

@include('partials.record_versions', ['versions' => $versions])

<section class="card" style="margin-top:16px">
    <h2>Advance Balance History</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th><th>Reference</th><th>Note</th></tr></thead>
        <tbody>
        @forelse ($customer->balanceTransactions as $transaction)
            <tr>
                <td>{{ $transaction->transaction_date?->format('Y-m-d') }}</td>
                <td><span class="badge {{ $transaction->direction === 'credit' ? 'active' : 'due' }}">{{ $transaction->direction }}</span></td>
                <td>{{ number_format($transaction->amount, 2) }}</td>
                <td>{{ number_format($transaction->balance_after, 2) }}</td>
                <td>{{ $transaction->reference ?? 'N/A' }}</td>
                <td>{{ $transaction->note ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No advance balance history yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
