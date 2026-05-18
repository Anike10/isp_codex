<form method="get" class="actions per-page-form">
    @foreach (request()->except(['per_page', 'page']) as $key => $value)
        @if (is_array($value))
            @foreach ($value as $item)
                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <label class="per-page-label">
        Entries
        <select name="per_page" class="per-page-select" onchange="this.form.submit()">
            @foreach (($perPageOptions ?? [25, 50, 100, 200]) as $option)
                <option value="{{ $option }}" @selected((int) request('per_page', $perPageDefault ?? 50) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </label>
</form>
