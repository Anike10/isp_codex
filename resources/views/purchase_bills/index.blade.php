@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Purchase Bills</h1><div class="muted">Wholesale/vendor product purchases, stock entry, serials, and warranty tracking</div></div>
    <a class="btn" href="{{ route('purchase-bills.create') }}">Add Purchase Bill</a>
</div>

@include('partials.per_page')

<table>
    <thead><tr><th>Bill</th><th>Vendor</th><th>Date</th><th>Total</th><th>Note</th></tr></thead>
    <tbody>
    @forelse ($purchaseBills as $purchaseBill)
        <tr data-href="{{ route('purchase-bills.show', $purchaseBill) }}">
            <td><strong>{{ $purchaseBill->bill_no }}</strong></td>
            <td>{{ $purchaseBill->party?->name ?? 'No vendor selected' }}</td>
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
