@php($rx = $row->onu_rx_power)
@if ($rx !== null)
    @php($rxTitle = $row->onu_id
        ? 'ONU #'.$row->onu_id.', read '.\Illuminate\Support\Carbon::parse($row->onu_rx_at)->format('d/m/Y H:i')
        : 'Captured from a disconnect webhook')
    <span class="badge {{ (float) $rx <= -25 ? 'failed' : 'active' }}" title="{{ $rxTitle }}">{{ number_format((float) $rx, 2) }} dBm</span>
@else
    <span class="muted">&mdash;</span>
@endif
