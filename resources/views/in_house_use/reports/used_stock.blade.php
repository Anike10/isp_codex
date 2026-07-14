@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Returned Used Stock Report</h1><div class="muted">Employee-returned items available for reissue; never mixed with new saleable stock</div></div><a class="btn" href="{{ route('in-house-use.index') }}">Reissue Used Stock</a></div>
@include('in_house_use.reports._nav')

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Product</label><input name="search" value="{{ request('search') }}" placeholder="Product name or SKU"></div>
    <div><label>Warehouse</label><select name="warehouse_id"><option value="">All warehouses</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((int)request('warehouse_id') === $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
    <div class="full actions"><button class="btn secondary" type="submit">Filter</button><a class="btn light" href="{{ route('in-house-use.report.used-stock') }}">Reset</a></div>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Product / Warehouse Rows</span><strong>{{ $usedStocks->count() }}</strong></div>
    <div class="card stat"><span class="muted">Total Used Units Available</span><strong>{{ $usedStocks->sum('quantity') }}</strong></div>
</div>

<section class="card">
    <table>
        <thead><tr><th>Product</th><th>SKU</th><th>Warehouse</th><th>Used Qty</th><th>Available Used Serials</th><th>Serial-less Used Qty</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($usedStocks as $stock)
            @php
                $serials = $stock->product->serials->where('warehouse_id', $stock->warehouse_id)->pluck('serial_number');
                $serialless = max(0, $stock->quantity - $serials->count());
            @endphp
            <tr><td>{{ $stock->product->name }}</td><td>{{ $stock->product->sku }}</td><td>{{ $stock->warehouse->name }}</td><td><strong>{{ $stock->quantity }}</strong></td><td>{{ $serials->isNotEmpty() ? app(\App\Support\SerialNumberParser::class)->formatCompact($serials->implode(', ')) : 'N/A' }}</td><td>{{ $serialless }}</td><td><a class="btn light" href="{{ route('in-house-use.index', ['product_id' => $stock->product_id, 'source_condition' => 'used']) }}">Issue to Employee</a></td></tr>
        @empty
            <tr><td colspan="7">No returned used stock available.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
