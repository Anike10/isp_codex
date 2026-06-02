@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Product Categories</h1><div class="muted">Fixed category tree for products and purchase bill selection</div></div>
    <a class="btn light" href="{{ route('products.index') }}">Back</a>
</div>

<form method="post" action="{{ route('product-categories.store') }}" class="card form-grid" style="margin-bottom:16px">
    @csrf
    <div>
        <label>Parent Category</label>
        <select name="parent_id">
            <option value="">Top level category</option>
            @foreach ($allCategories as $category)
                <option value="{{ $category->id }}" @selected((int) old('parent_id') === $category->id)>
                    {{ implode(' / ', $category->pathNames()) }}
                </option>
            @endforeach
        </select>
    </div>
    <div><label>Category Name</label><input name="name" value="{{ old('name') }}" required></div>
    <div class="full"><button class="btn" type="submit">Save Category</button></div>
</form>

<section class="card">
    <h2>Category Tree</h2>
    @forelse ($categories as $category)
        @include('product_categories.partials.node', ['category' => $category, 'level' => 0])
    @empty
        <p class="muted">No categories yet.</p>
    @endforelse
</section>
@endsection
