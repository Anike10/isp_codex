@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>ONU Details</h1>
        <div class="muted">Details for ONU {{ $oltOnu->pon_port }}/{{ $oltOnu->onu_id }} on {{ $oltDevice->name }}</div>
    </div>
    <div class="actions">
        <a class="btn light" href="{{ route('olt-onus.index') }}">Back to ONU list</a>
        <a class="btn light" href="{{ route('olt-onus.olts.edit', $oltDevice) }}">Edit OLT</a>
        <form method="post" action="{{ route('olt-onus.refresh', $oltOnu) }}" style="display:inline-block">
            @csrf
            <button class="btn secondary" type="submit">Refresh Live Data</button>
        </form>
    </div>
</div>

<div class="grid" style="gap:16px">
    <div class="card">
        <h2>Summary</h2>
        <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px;">
            <div class="summary-item">
                <span class="muted">OLT</span>
                <strong>{{ $oltDevice->name }}</strong>
                <div class="muted">{{ $oltDevice->host }}:{{ $oltDevice->port }}</div>
            </div>
            <div class="summary-item">
                <span class="muted">PON / ONU</span>
                <strong>{{ $oltOnu->pon_port }}/{{ $oltOnu->onu_id }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Name</span>
                <strong>{{ $oltOnu->name ?: 'N/A' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Serial / MAC</span>
                <strong>{{ $oltOnu->mac_address ?: 'N/A' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Status</span>
                <strong>{{ $oltOnu->status ?: 'unknown' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Last Live Poll</span>
                <strong>{{ $oltOnu->last_live_polled_at?->format('Y-m-d H:i:s') ?? 'Never' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Power</span>
                <div>
                    @if ($oltOnu->rx_power_dbm !== null)
                        @php $powerClass = $oltOnu->rx_power_dbm <= -25 ? 'failed' : 'active'; @endphp
                        <span class="badge {{ $powerClass }}" style="font-size:1rem; padding:10px 12px; display:inline-block; min-width:120px; text-align:center;">
                            {{ number_format((float) $oltOnu->rx_power_dbm, 2) }} dBm
                        </span>
                    @else
                        <span class="muted">No live power</span>
                    @endif
                </div>
            </div>
            <div class="summary-item" style="grid-column: span 3;">
                <span class="muted">Description</span>
                <div style="padding:12px 14px; background:rgba(0,0,0,.03); border-radius:8px; min-height:48px;">
                    {{ $oltOnu->description ?: 'N/A' }}
                </div>
            </div>
            <div class="summary-item">
                <span class="muted">Last Registered</span>
                <strong>{{ $oltOnu->last_registered_at?->format('Y-m-d H:i:s') ?? 'Never' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Last Deregistered</span>
                <div>
                    @if ($oltOnu->last_deregistered_at)
                        <strong>{{ $oltOnu->last_deregistered_at->format('Y-m-d H:i:s') }}</strong>
                        @if ($oltOnu->last_deregister_reason)
                            <div class="muted" style="margin-top:6px;">{{ $oltOnu->last_deregister_reason }}</div>
                        @endif
                    @else
                        <strong>Never</strong>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Configured VLANs</h2>
        @forelse ($oltOnu->port_vlans ?? [] as $vlan)
            <div class="badge">Port {{ $vlan['port'] ?? '?' }}: {{ $vlan['vlan'] ?? ($vlan['mode'] ?? '?') }}</div>
        @empty
            <div class="muted">No VLAN configuration available.</div>
        @endforelse
    </div>
</div>

<div class="card" style="margin-top:16px">
    <h2>Learned MACs</h2>
    @forelse ($oltOnu->learned_macs ?? [] as $learnedMac)
        <div style="margin-bottom:8px">
            <strong>{{ $learnedMac['mac'] ?? '?' }}</strong>
            @if (isset($learnedMac['vlan']))
                <span class="muted">VLAN {{ $learnedMac['vlan'] }}</span>
            @endif
        </div>
    @empty
        <div class="muted">No learned MAC addresses.</div>
    @endforelse
</div>

<div class="card" style="margin-top:16px">
    <h2>Raw Live Output</h2>
    <pre style="white-space:pre-wrap; overflow:auto; margin:0">{{ $oltOnu->raw_live_output ?: 'No live output recorded yet.' }}</pre>
</div>

<div class="card" style="margin-top:16px">
    <h2>Raw Interface Config</h2>
    <pre style="white-space:pre-wrap; overflow:auto; margin:0">{{ $oltOnu->raw_interface_config ?: 'No interface config recorded yet.' }}</pre>
</div>
@endsection
