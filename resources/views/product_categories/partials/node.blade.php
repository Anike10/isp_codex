<div style="margin-left:{{ $level * 18 }}px; padding:6px 0; display:flex; gap:8px; align-items:flex-start; flex-wrap:wrap">
    <span class="badge {{ $level === 0 ? 'active' : 'pending' }}">{{ $category->name }}</span>
    <details>
        <summary class="btn light">Edit</summary>
        <form method="post" action="{{ route('product-categories.update', $category) }}" class="card" style="margin-top:8px; min-width:min(420px, 85vw)">
            @csrf
            @method('PUT')
            <label>Parent Category</label>
            <select name="parent_id">
                <option value="">Top level category</option>
                @foreach ($allCategories as $parentOption)
                    @if ($parentOption->id !== $category->id)
                        <option value="{{ $parentOption->id }}" @selected($category->parent_id === $parentOption->id)>{{ implode(' / ', $parentOption->pathNames()) }}</option>
                    @endif
                @endforeach
            </select>
            <label>Category Name</label>
            <input name="name" value="{{ $category->name }}" required>
            <button class="btn" type="submit">Update Category</button>
        </form>
    </details>
    <form method="post" action="{{ route('product-categories.destroy', $category) }}" onsubmit="return confirm('Delete category {{ addslashes($category->name) }}? Only empty categories can be deleted.')">
        @csrf
        @method('DELETE')
        <button class="btn danger" type="submit">Delete</button>
    </form>
</div>
@foreach ($category->children as $child)
    @include('product_categories.partials.node', ['category' => $child, 'level' => $level + 1])
@endforeach
