@php($rx = $row->onu_rx_power)
@php($tx = $row->onu_tx_power ?? null)
@if ($rx !== null || $tx !== null)
    @php($title = $row->onu_id
        ? 'ONU #'.$row->onu_id.', read '.\Illuminate\Support\Carbon::parse($row->onu_rx_at)->format('d/m/Y H:i')
        : 'Captured from a disconnect webhook')
    <span style="white-space:nowrap" title="{{ $title }}">
        @if ($rx !== null)
            <span class="badge {{ (float) $rx <= -25 ? 'failed' : 'active' }}">Rx {{ number_format((float) $rx, 2) }}</span>
        @else
            <span class="badge">Rx &mdash;</span>
        @endif
        @if ($tx !== null)
            <span class="badge {{ ((float) $tx <= 0.5 || (float) $tx >= 7) ? 'failed' : 'active' }}">Tx {{ number_format((float) $tx, 2) }}</span>
        @else
            <span class="badge">Tx &mdash;</span>
        @endif
    </span>
@else
    <span class="muted">&mdash;</span>
@endif
