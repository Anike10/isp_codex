@extends('layouts.app')

@section('content')
@php
    $canOpenPartyLedger = auth()->user()?->hasPermission('manage_payment_accounts') || auth()->user()?->hasPermission('manage_customers');
@endphp
<div class="topbar">
    <div><h1>Purchase Bills</h1><div class="muted">Wholesale/vendor product purchases, stock entry, serials, and warranty tracking</div></div>
    <a class="btn" href="{{ route('purchase-bills.create') }}">Add Purchase Bill</a>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Bill no, vendor, product, serial, phone, connection ID, or note"></div>
    <div><label>Vendor</label><select name="party_id"><option value="">All vendors</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected((int) request('party_id') === $vendor->id)>{{ $vendor->name }} - {{ $vendor->phone }}</option>@endforeach</select></div>
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Min Amount</label><input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}"></div>
    <div><label>Max Amount</label><input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('purchase-bills.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Bill</th><th>Vendor Party</th><th>Date</th><th>Total</th><th>Note</th></tr></thead>
    <tbody>
    @forelse ($purchaseBills as $purchaseBill)
        <tr data-href="{{ route('purchase-bills.show', $purchaseBill) }}">
            <td><strong>{{ $purchaseBill->bill_no }}</strong></td>
            <td>
                @if ($purchaseBill->party)
                    @if ($canOpenPartyLedger)
                        <a href="{{ route('accounting.ledger', ['customer_id' => $purchaseBill->party_id]) }}">{{ $purchaseBill->party->name }}</a>
                    @else
                        {{ $purchaseBill->party->name }}
                    @endif
                @else
                    No vendor selected
                @endif
            </td>
            <td>{{ $purchaseBill->purchase_date->format('Y-m-d') }}</td>
            <td>{{ number_format($purchaseBill->subtotal, 2) }}</td>
            <td>{{ $purchaseBill->note ?? 'N/A' }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No purchase bills found.</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $purchaseBills->links() }}</div>
@endsection
