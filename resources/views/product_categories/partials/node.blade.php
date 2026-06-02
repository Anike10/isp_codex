<div style="margin-left:{{ $level * 18 }}px; padding:6px 0">
    <span class="badge {{ $level === 0 ? 'active' : 'pending' }}">{{ $category->name }}</span>
</div>
@foreach ($category->children as $child)
    @include('product_categories.partials.node', ['category' => $child, 'level' => $level + 1])
@endforeach
