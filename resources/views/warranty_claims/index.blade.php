@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Warranty Claims</h1><div class="muted">Track customer claims, repairs, replacements, vendor returns, and paid service.</div></div>
    @if (auth()->user()?->hasPermission('manage_warranty_claims') || auth()->user()?->hasPermission('manage_products'))
        <a class="btn" href="{{ route('warranty-claims.create') }}">New Claim</a>
    @endif
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Claim, customer, phone, product, serial"></div>
    <div>
        <label>Status</label>
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="full actions">
        <button class="btn" type="submit">Filter</button>
        <a class="btn light" href="{{ route('warranty-claims.index') }}">Reset</a>
    </div>
</form>

@include('partials.per_page')

<table>
    <thead>
        <tr><th>Claim</th><th>Customer</th><th>Product</th><th>Serial</th><th>Warranty</th><th>Status</th><th>Service Amount</th><th>Date</th></tr>
    </thead>
    <tbody>
    @forelse ($claims as $claim)
        <tr data-href="{{ route('warranty-claims.show', $claim) }}">
            <td><a href="{{ route('warranty-claims.show', $claim) }}">{{ $claim->claim_no }}</a></td>
            <td>{{ $claim->customer->name }}<br><span class="muted">{{ $claim->customer->phone }}</span></td>
            <td>{{ $claim->product?->name ?? 'Manual claim' }}</td>
            <td>{{ $claim->productSerial?->serial_number ?? 'N/A' }}</td>
            <td><span class="badge {{ $claim->warranty_status === 'in_warranty' ? 'active' : ($claim->warranty_status === 'expired' ? 'due' : 'inactive') }}">{{ str_replace('_', ' ', $claim->warranty_status) }}</span></td>
            <td><span class="badge {{ in_array($claim->status, ['closed', 'delivered', 'replaced'], true) ? 'active' : ($claim->status === 'rejected' ? 'failed' : 'pending') }}">{{ str_replace('_', ' ', $claim->status) }}</span></td>
            <td>
                {{ number_format($claim->service_charge, 2) }}
                @if ($claim->serviceInvoice)
                    <div class="muted">{{ $claim->serviceInvoice->invoice_no }} - {{ $claim->serviceInvoice->status }}</div>
                @elseif ((float) $claim->service_charge > 0)
                    <div class="muted">Estimate only</div>
                @endif
            </td>
            <td>{{ $claim->claim_date?->format('Y-m-d') }}</td>
        </tr>
    @empty
        <tr><td colspan="8">No warranty claims found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $claims->links() }}</div>
@endsection
