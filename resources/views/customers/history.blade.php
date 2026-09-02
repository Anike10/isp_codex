@extends('layouts.app')

@section('content')
@php
    $isDeletedCustomer = method_exists($customer, 'trashed') && $customer->trashed();
    $subscription = $customer->activeSubscription ?: $customer->latestSubscription;
    $roleParts = array_filter([
        $customer->is_customer ? 'Customer' : null,
        $customer->is_reseller ? 'Reseller' : null,
        $customer->is_vendor ? 'Vendor' : null,
    ]);
    $totalDue = $customer->relationLoaded('invoices') ? (float) $customer->invoices->sum('due_amount') : null;
    $hasMapLocation = is_numeric($customer->map_latitude) && is_numeric($customer->map_longitude);
@endphp
<div class="customer-shell">
    <section class="topbar">
        <div>
            <h1>{{ $customer->name }} <span class="muted">- {{ $isDeletedCustomer ? 'Deleted Party' : 'Edit History' }}</span></h1>
            <div class="muted">
                {{ $customer->connection_id ?: 'ID not assigned' }} &bull; {{ $customer->phone ?: 'Phone not provided' }}
            </div>
            @if ($subscription?->package)
                <div class="muted" style="margin-top:4px;">
                    Package: {{ $subscription->package->name }}
                </div>
            @endif
        </div>
        <div class="actions" style="gap:8px">
            @if ($isDeletedCustomer)
                <form method="post" action="{{ route('customers.restore', $customer->id) }}" onsubmit="return confirm('Restore this party to active list?');" style="display:inline">
                    @csrf
                    <button class="btn secondary" type="submit">Restore</button>
                </form>
                <a class="btn light" href="{{ route('customers.deleted') }}">Back to Deleted Parties</a>
            @else
                <a class="btn" href="{{ route('customers.show', $customer) }}">Back to Profile</a>
            @endif
        </div>
    </section>

    @if ($isDeletedCustomer)
        <section class="card" style="margin-bottom:14px;">
            <h2>Party Details</h2>
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px 20px">
                <p><strong>Name:</strong> {{ $customer->name }}</p>
                <p><strong>Role:</strong> {{ $roleParts ? implode(' + ', $roleParts) : '—' }}</p>
                <p><strong>Phone:</strong> {{ $customer->phone ?: '—' }}</p>
                <p><strong>Email:</strong> {{ $customer->email ?: '—' }}</p>
                <p><strong>Connection ID:</strong> {{ $customer->connection_id ?: '—' }}</p>
                <p><strong>MikroTik username:</strong> {{ $customer->mikrotik_username ?: '—' }}</p>
                <p><strong>MikroTik router:</strong> {{ $customer->mikrotikRouter?->name ?: '—' }}</p>
                <p><strong>Package:</strong> {{ $subscription?->package?->name ?: '—' }}</p>
                <p><strong>Status at deletion:</strong> {{ ucfirst($customer->status ?: '—') }}</p>
                <p><strong>Account balance:</strong> ৳ {{ number_format((float) $customer->account_balance, 2) }}</p>
                <p><strong>Total due:</strong> {{ $totalDue === null ? '—' : '৳ '.number_format($totalDue, 2) }}</p>
                <p><strong>Reseller:</strong> {{ $customer->reseller?->name ?: '—' }}</p>
                <p><strong>Never suspend:</strong> {{ $customer->never_suspend ? 'Yes' : 'No' }}</p>
                <p><strong>Service valid until:</strong> {{ $customer->service_valid_until?->format('d/m/Y') ?: '—' }}</p>
                <p><strong>Grace until:</strong> {{ $customer->grace_until?->format('d/m/Y') ?: '—' }}</p>
                <p><strong>Last connected:</strong> {{ $customer->last_connected_at?->format('d/m/Y H:i') ?: '—' }}
                    @if ($customer->last_connected_mac) <span class="muted">({{ $customer->last_connected_mac }})</span>@endif
                </p>
                <p><strong>Deleted at:</strong> {{ $customer->deleted_at?->format('d/m/Y H:i') ?: '—' }}</p>
                <p style="grid-column:1/-1"><strong>Address:</strong> {{ $customer->address ?: '—' }}</p>
                <p style="grid-column:1/-1"><strong>Map location:</strong>
                    @if ($hasMapLocation)
                        <a href="https://maps.google.com/?q={{ $customer->map_latitude }},{{ $customer->map_longitude }}" target="_blank" rel="noopener">{{ $customer->map_latitude }}, {{ $customer->map_longitude }}</a>
                    @else
                        Not set
                    @endif
                </p>
                @if (filled($customer->notes))
                    <p style="grid-column:1/-1"><strong>Note:</strong> <span style="white-space:pre-wrap">{{ $customer->notes }}</span></p>
                @endif
            </div>
        </section>
    @endif

    <section class="card" style="margin-bottom: 14px;">
        <h2>Customer Change Log</h2>
        <p style="margin-bottom:0;">Every edit made to this profile is recorded here.</p>
    </section>

    @include('partials.record_versions', ['versions' => $versions])
</div>
@endsection
