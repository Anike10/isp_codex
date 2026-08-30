@php
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
@endphp

@if ($total === 0)
    <p class="muted" style="margin:8px 0 0">No rows.</p>
@elseif ($single)
    <div class="table-wrap" style="overflow:auto;margin-top:8px">
        <table class="rd-kv">
            <tbody>
                @foreach ((array) $rows[0] as $key => $value)
                    <tr>
                        <th>{{ $key }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="table-wrap" style="overflow:auto;max-height:540px;margin-top:8px">
        <table class="rd-rows">
            <thead>
                <tr>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>@foreach ($columns as $column)<td>{{ ((array) $row)[$column] ?? '' }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($total > $rowCap)
        <p class="muted" style="margin:8px 0 0">Showing {{ $tail ? 'last' : 'first' }} {{ number_format($rowCap) }} of {{ number_format($total) }} rows.</p>
    @endif
@endif
