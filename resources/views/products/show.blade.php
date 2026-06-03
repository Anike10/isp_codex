@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $product->name }}</h1>
        <div class="muted">{{ $product->sku }}{{ $product->barcode ? ' - Barcode: '.$product->barcode : '' }} - {{ $product->brand ?? 'No brand' }} - {{ $product->category ?? 'No category' }}{{ $product->subcategory ? ' / '.$product->subcategory : '' }}</div>
    </div>
    <a class="btn light" href="{{ route('products.index') }}">Back</a>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Type</span><strong>{{ str_replace('_', ' ', ucfirst($product->product_type ?? ($product->track_inventory ? 'stock' : 'service'))) }}</strong></div>
    <div class="card stat"><span class="muted">Inventory</span><strong>{{ $product->track_inventory ? 'Tracked' : 'Not tracked' }}</strong></div>
    <div class="card stat"><span class="muted">Current Stock</span><strong>{{ $product->track_inventory ? $product->stock_quantity : 'N/A' }}</strong></div>
    <div class="card stat"><span class="muted">Serial</span><strong>{{ $product->track_serial_numbers ? 'Tracked' : 'Not tracked' }}</strong></div>
    <div class="card stat"><span class="muted">Warranty</span><strong>{{ $product->warranty_days !== null ? $product->warranty_days.' days' : ($product->service_guarantee_days ? $product->service_guarantee_days.' service days' : 'N/A') }}</strong></div>
    <div class="card stat"><span class="muted">Purchase Price</span><strong>{{ number_format($product->purchase_price, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Sale Price</span><strong>{{ number_format($product->sale_price, 2) }}</strong></div>
</div>

@if ($product->track_inventory)
    <section class="card" style="margin-bottom:16px">
        <h2>Move Stock</h2>
        <form method="post" action="{{ route('products.stock', $product) }}" class="actions">
            @csrf
            <select name="type" style="width:auto"><option value="in">In</option><option value="out">Out</option><option value="use">Own Use</option></select>
            <input type="number" name="quantity" min="1" placeholder="Qty" style="width:120px" required>
            <input name="reason" placeholder="Reason" style="width:220px">
            @if ($product->track_serial_numbers)
                <textarea name="serial_numbers" rows="2" placeholder="Serials: 1001-1010, 1020-1030" style="min-width:280px"></textarea>
            @endif
            <button class="btn secondary" type="submit">Update Stock</button>
        </form>
        @if ($product->track_serial_numbers)
            <div class="muted" style="margin-top:8px">For Own Use/Out, serial count must match quantity. You can use comma, new line, or range.</div>
        @endif
    </section>
@endif

<section class="card" style="margin-bottom:16px">
    <h2>Serials & Warranty</h2>
    @if ($product->track_serial_numbers)
        <div class="actions" style="margin-bottom:14px">
            <span class="badge">In House: {{ $serialGroups['in_stock'] ?? 0 }}</span>
            <span class="badge">Own Use: {{ $serialGroups['used'] ?? 0 }}</span>
            <span class="badge">Out: {{ $serialGroups['out'] ?? 0 }}</span>
        </div>
    @endif
    <table>
        <thead><tr><th>Serial</th><th>Status</th><th>Customer</th><th>Warranty Until</th><th>Purchase Bill</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($serials as $serial)
            <tr>
                <td><span class="badge">{{ $serial->serial_number }}</span></td>
                <td>{{ $serial->status === 'in_stock' ? 'In house' : str_replace('_', ' ', ucfirst($serial->status)) }}</td>
                <td>{{ $serial->customer?->name ?? 'N/A' }}</td>
                <td>{{ $serial->warranty_until?->format('Y-m-d') ?? 'No warranty' }}</td>
                <td>{{ $serial->purchaseBill?->bill_no ?? 'N/A' }}</td>
                <td>
                    @if ($serial->customer_id && auth()->user()?->hasPermission('manage_warranty_claims'))
                        <a class="btn light" href="{{ route('warranty-claims.create', ['product_serial_id' => $serial->id]) }}">Claim</a>
                    @else
                        {{ $serial->note ?? 'N/A' }}
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No serial tracked for this product.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

@include('partials.per_page')

<table>
    <thead><tr><th>Date</th><th>Type</th><th>Quantity</th><th>Reason</th><th>Reference</th></tr></thead>
    <tbody>
    @forelse ($stockMovements as $movement)
        <tr>
            <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
            <td>{{ $movement->type === 'use' ? 'Own Use' : ucfirst($movement->type) }}</td>
            <td>{{ $movement->quantity }}</td>
            <td>{{ $movement->reason ?? 'N/A' }}</td>
            <td>{{ $movement->reference_no ?? 'N/A' }}</td>
        </tr>
    @empty
        <tr><td colspan="5">No stock movement recorded yet.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $stockMovements->links() }}</div>
@endsection
