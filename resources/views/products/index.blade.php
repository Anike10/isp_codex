@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Inventory</h1><div class="muted">Routers, cable, computer parts and accessories</div></div>
    <a class="btn" href="{{ route('products.create') }}">Add Product</a>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Name, SKU, brand, category"></div>
    <div>
        <label>Brand</label>
        <select name="brand">
            <option value="">All brands</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Category</label>
        <select name="category">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Sub Category</label>
        <select name="subcategory">
            <option value="">All sub categories</option>
            @foreach ($subcategories as $subcategory)
                <option value="{{ $subcategory }}" @selected(request('subcategory') === $subcategory)>{{ $subcategory }}</option>
            @endforeach
        </select>
    </div>
    <div class="full actions">
        <button class="btn secondary" type="submit">Filter</button>
        <a class="btn light" href="{{ route('products.index') }}">Reset</a>
    </div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Product</th><th>SKU</th><th>Brand</th><th>Category</th><th>Sub Category</th><th>Stock</th><th>Sale Price</th><th>Move Stock</th></tr></thead>
    <tbody>
    @forelse ($products as $product)
        <tr data-href="{{ route('products.show', $product) }}">
            <td>{{ $product->name }}</td>
            <td>{{ $product->sku }}</td>
            <td>{{ $product->brand ?? 'N/A' }}</td>
            <td>{{ $product->category ?? 'N/A' }}</td>
            <td>{{ $product->subcategory ?? 'N/A' }}</td>
            <td>
                {{ $product->stock_quantity }}
                @if ($product->isLowStock())
                    <span class="badge low">low</span>
                @endif
            </td>
            <td>{{ number_format($product->sale_price, 2) }}</td>
            <td>
                <form method="post" action="{{ route('products.stock', $product) }}" class="actions">
                    @csrf
                    <select name="type" style="width:auto"><option value="in">In</option><option value="out">Out</option><option value="use">Own Use</option></select>
                    <input type="number" name="quantity" min="1" placeholder="Qty" style="width:90px" required>
                    <input name="reason" placeholder="Reason" style="width:150px">
                    <button class="btn secondary" type="submit">Update</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="8">No products found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $products->links() }}</div>
@endsection
