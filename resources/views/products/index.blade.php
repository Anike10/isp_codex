@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Inventory</h1><div class="muted">Routers, cable, computer parts and accessories</div></div>
    <a class="btn" href="{{ route('products.create') }}">Add Product</a>
</div>

@include('partials.per_page')

<table>
    <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Stock</th><th>Sale Price</th><th>Move Stock</th></tr></thead>
    <tbody>
    @forelse ($products as $product)
        <tr data-href="{{ route('products.show', $product) }}">
            <td>{{ $product->name }}</td>
            <td>{{ $product->sku }}</td>
            <td>{{ $product->category }}</td>
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
        <tr><td colspan="6">No products found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $products->links() }}</div>
@endsection
