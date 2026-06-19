@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>{{ $warehouse->name }}</h1><div class="muted">{{ $warehouse->code }} · {{ $warehouse->address ?: 'No address' }}</div></div>
    <div class="actions">
        <a class="btn" href="{{ route('warehouse-transfers.create', ['from_warehouse_id' => $warehouse->id]) }}">Transfer Stock</a>
        <a class="btn light" href="{{ route('warehouse-movements.index', ['warehouse_id' => $warehouse->id]) }}">Full History</a>
        <a class="btn light" href="{{ route('warehouses.index') }}">Back</a>
    </div>
</div>

<form method="get" class="card actions" style="margin-bottom:16px">
    <input name="search" value="{{ request('search') }}" placeholder="Product name or SKU">
    <button class="btn secondary" type="submit">Search</button>
</form>

<h2>Current Stock</h2>
<table>
    <thead><tr><th>Product</th><th>SKU</th><th>Quantity</th><th>Tracked Serials</th><th>Serial-less</th><th>Action</th></tr></thead>
    <tbody>
    @forelse($stocks as $stock)
        @php($serialCount = $stock->product->serials->count())
        <tr>
            <td>{{ $stock->product->name }}</td>
            <td>{{ $stock->product->sku }}</td>
            <td>{{ $stock->quantity }}</td>
            <td>{{ $stock->product->track_serial_numbers ? $serialCount : 'N/A' }}</td>
            <td>{{ $stock->product->track_serial_numbers ? max(0, $stock->quantity - $serialCount) : 'N/A' }}</td>
            <td><a class="btn light" href="{{ route('products.show', $stock->product) }}">Product Details</a></td>
        </tr>
    @empty
        <tr><td colspan="6">No stock in this warehouse.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $stocks->links() }}</div>

<h2 style="margin-top:24px">Recent In / Out History</h2>
@include('warehouses.partials.movement_table', ['movements' => $recentMovements])
@endsection
