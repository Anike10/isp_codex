<form method="get" class="actions per-page-form {{ $perPageFormClass ?? '' }}" style="{{ $perPageFormStyle ?? '' }}">
    @foreach (request()->except(['per_page', 'page', 'make_per_page_default']) as $key => $value)
        @if (is_array($value))
            @foreach ($value as $item)
                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <label class="per-page-label">
        Rows per page
        <input
            type="number"
            name="per_page"
            class="per-page-select"
            min="1"
            max="20000"
            list="per-page-presets"
            value="{{ (int) ($perPage ?? request('per_page', $perPageDefault ?? 50)) }}"
            onchange="this.form.submit()"
        >
        <datalist id="per-page-presets">
            @foreach (($perPageOptions ?? [25, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000]) as $option)
                <option value="{{ $option }}"></option>
            @endforeach
        </datalist>
    </label>
    <button class="btn light" type="submit" name="make_per_page_default" value="1">Set as Default</button>
</form>
