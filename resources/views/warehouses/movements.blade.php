@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Stock Movement History</h1><div class="muted">Warehouse-wise In, Out, Own Use and Transfer ledger</div></div>
    <a class="btn light" href="{{ route('warehouses.index') }}">Warehouses</a>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Warehouse</label><select name="warehouse_id"><option value="">All</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((int)request('warehouse_id') === $warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
    <div><label>Product</label><select name="product_id"><option value="">All</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((int)request('product_id') === $product->id)>{{ $product->name }} ({{ $product->sku }})</option>@endforeach</select></div>
    <div><label>Type</label><select name="type"><option value="">All</option>@foreach(['in'=>'In','out'=>'Out','use'=>'Own Use','transfer_in'=>'Transfer In','transfer_out'=>'Transfer Out'] as $value=>$label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Product, SKU, warehouse, reference, serial, or reason"></div>
    <div><label>From Date</label><input type="date" name="date_from" value="{{ request('date_from') }}"></div>
    <div><label>To Date</label><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
    <div class="actions"><button class="btn secondary" type="submit">Filter</button><a class="btn light" href="{{ route('warehouse-movements.index') }}">Reset</a></div>
</form>

@include('warehouses.partials.movement_table', ['movements' => $movements, 'referenceLinks' => $referenceLinks])
<div style="margin-top:16px">{{ $movements->links() }}</div>
@endsection
