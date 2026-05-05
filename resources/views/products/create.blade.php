@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Product</h1><div class="muted">Create stock item for computer shop or ISP inventory</div></div>
    <a class="btn light" href="{{ route('products.index') }}">Back</a>
</div>

<form method="post" action="{{ route('products.store') }}" class="card form-grid">
    @csrf
    <div><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
    <div><label>SKU</label><input name="sku" value="{{ old('sku') }}" required></div>
    <div><label>Category</label><input name="category" value="{{ old('category') }}" placeholder="Router, Cable, RAM"></div>
    <div><label>Purchase Price</label><input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', 0) }}" required></div>
    <div><label>Sale Price</label><input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', 0) }}" required></div>
    <div><label>Opening Stock</label><input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" required></div>
    <div><label>Low Stock Alert</label><input type="number" name="low_stock_alert" value="{{ old('low_stock_alert', 5) }}" required></div>
    <div class="full"><button class="btn" type="submit">Save Product</button></div>
</form>
@endsection
