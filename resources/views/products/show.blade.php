@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $product->name }}</h1>
        <div class="muted">{{ $product->sku }} - {{ $product->category ?? 'No category' }}</div>
    </div>
    <a class="btn light" href="{{ route('products.index') }}">Back</a>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Current Stock</span><strong>{{ $product->stock_quantity }}</strong></div>
    <div class="card stat"><span class="muted">Low Stock Alert</span><strong>{{ $product->low_stock_alert }}</strong></div>
    <div class="card stat"><span class="muted">Purchase Price</span><strong>{{ number_format($product->purchase_price, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Sale Price</span><strong>{{ number_format($product->sale_price, 2) }}</strong></div>
</div>

<section class="card" style="margin-bottom:16px">
    <h2>Move Stock</h2>
    <form method="post" action="{{ route('products.stock', $product) }}" class="actions">
        @csrf
        <select name="type" style="width:auto"><option value="in">In</option><option value="out">Out</option></select>
        <input type="number" name="quantity" min="1" placeholder="Qty" style="width:120px" required>
        <input name="reason" placeholder="Reason" style="width:220px">
        <button class="btn secondary" type="submit">Update Stock</button>
    </form>
</section>

<table>
    <thead><tr><th>Date</th><th>Type</th><th>Quantity</th><th>Reason</th><th>Reference</th></tr></thead>
    <tbody>
    @forelse ($product->stockMovements as $movement)
        <tr>
            <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
            <td>{{ ucfirst($movement->type) }}</td>
            <td>{{ $movement->quantity }}</td>
            <td>{{ $movement->reason ?? 'N/A' }}</td>
            <td>{{ $movement->reference_no ?? 'N/A' }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No stock movement recorded yet.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
