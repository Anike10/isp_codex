<form method="get" class="actions" style="justify-content:flex-end; margin:0 0 12px">
    @foreach (request()->except(['per_page', 'page']) as $key => $value)
        @if (is_array($value))
            @foreach ($value as $item)
                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <label style="margin:0; display:flex; align-items:center; gap:8px; font-weight:700;">
        Entries
        <select name="per_page" onchange="this.form.submit()" style="width:auto; min-width:90px">
            @foreach ([25, 50, 100, 200] as $option)
                <option value="{{ $option }}" @selected((int) request('per_page', 50) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </label>
</form>
