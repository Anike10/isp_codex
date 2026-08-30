@php
    use App\Support\RouterOsValue;

    $records = $records ?? [];
    $rowCap = $rowCap ?? 500;
    $tail = $tail ?? false;
    $total = count($records);
    $rows = $tail ? array_slice($records, -$rowCap) : array_slice($records, 0, $rowCap);
    $single = $total === 1;

    $columns = [];
    if (! $single) {
        foreach ($rows as $row) {
            foreach (array_keys((array) $row) as $key) {
                if (! in_array($key, $columns, true)) {
                    $columns[] = $key;
                }
            }
        }
        $idAt = array_search('.id', $columns, true);
        if ($idAt !== false) {
            unset($columns[$idAt]);
            array_unshift($columns, '.id');
            $columns = array_values($columns);
        }
    }

    // Render one value: humanised text, wrapped in <code>/<span class="badge">
    // where useful. Returns ready HTML; the raw value goes in the title.
    $render = function (string $key, $value): string {
        $raw = trim((string) $value);
        $text = e(RouterOsValue::humanize($key, $raw));
        $kind = RouterOsValue::kind($key, $raw);
        $title = ($text !== e($raw) && $raw !== '') ? ' title="'.e($raw).'"' : '';

        if ($kind === 'bool-on') {
            return '<span class="badge active"'.$title.'>'.$text.'</span>';
        }
        if ($kind === 'bool-off') {
            return '<span class="badge inactive"'.$title.'>'.$text.'</span>';
        }
        if ($text === '') {
            return '<span class="muted">&mdash;</span>';
        }
        if ($kind === 'mono') {
            return '<code'.$title.'>'.$text.'</code>';
        }

        return '<span'.$title.'>'.$text.'</span>';
    };
@endphp

@if ($total === 0)
    <p class="muted" style="margin:8px 0 0">No rows.</p>
@elseif ($single)
    <dl class="rd-kv">
        @foreach ((array) $rows[0] as $key => $value)
            <div class="rd-kv__row"><dt>{{ $key }}</dt><dd>{!! $render($key, $value) !!}</dd></div>
        @endforeach
    </dl>
@else
    <div class="rd-scroll">
        <table class="rd-rows">
            <thead>
                <tr>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>@foreach ($columns as $column)<td>{!! $render($column, ((array) $row)[$column] ?? '') !!}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($total > $rowCap)
        <p class="muted" style="margin:8px 0 0">Showing {{ $tail ? 'last' : 'first' }} {{ number_format($rowCap) }} of {{ number_format($total) }} rows.</p>
    @endif
@endif
