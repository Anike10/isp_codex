@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>{{ $purchaseBill->bill_no }}</h1><div class="muted">{{ $purchaseBill->purchase_date->format('Y-m-d') }} - {{ $purchaseBill->party?->name ?? 'No vendor selected' }}</div></div>
    <a class="btn light" href="{{ route('purchase-bills.index') }}">Back</a>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Vendor</span><strong style="font-size:18px">{{ $purchaseBill->party?->name ?? 'N/A' }}</strong></div>
    <div class="card stat"><span class="muted">Items</span><strong>{{ $purchaseBill->items->count() }}</strong></div>
    <div class="card stat"><span class="muted">Total</span><strong>{{ number_format($purchaseBill->subtotal, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Date</span><strong style="font-size:18px">{{ $purchaseBill->purchase_date->format('Y-m-d') }}</strong></div>
</div>

<section class="card">
    <h2>Purchased Products</h2>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Total</th><th>Warranty</th><th>Serials</th></tr></thead>
        <tbody>
        @foreach ($purchaseBill->items as $item)
            <tr>
                <td>
                    {{ $item->product->name }} <span class="muted">{{ $item->product->sku }}</span>
                    <div class="muted">{{ $item->product->brand ?? 'No brand' }} - {{ $item->product->category ?? 'No category' }}{{ $item->product->subcategory ? ' / '.$item->product->subcategory : '' }}</div>
                </td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
                <td>{{ $item->warranty_months ? $item->warranty_months.' month(s)' : 'No warranty' }}</td>
                <td>
                    @forelse ($item->serials as $serial)
                        <div><span class="badge">{{ $serial->serial_number }}</span> <span class="muted">{{ $serial->warranty_until ? 'Warranty until '.$serial->warranty_until->format('Y-m-d') : 'No warranty date' }}</span></div>
                    @empty
                        <span class="muted">No serial tracked</span>
                    @endforelse
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>
@endsection
