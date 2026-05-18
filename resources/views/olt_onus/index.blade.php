@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>OLT ONUs</h1>
        <div class="muted">Live ONU status and optical power from configured OLT devices.</div>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('olt-onus.olts.create') }}">Add OLT</a>
    </div>
</div>

@if ($oltDevices->isNotEmpty())
    <div class="card" style="margin-bottom:16px">
        <h2>Live Refresh</h2>
        <div class="grid">
            @foreach ($oltDevices as $oltDevice)
                <div class="actions" style="justify-content:space-between; border-bottom:1px solid var(--line); padding:10px 0">
                    <div>
                        <strong>{{ $oltDevice->name }}</strong>
                        <div class="muted">
                            {{ $oltDevice->host }}:{{ $oltDevice->port }}
                            | {{ $oltDevice->brand ?: 'Unknown Brand' }}
                            | {{ $protocolProfiles[$oltDevice->protocol_profile] ?? $oltDevice->protocol_profile ?? 'Unknown Profile' }}
                            | Last: {{ $oltDevice->last_polled_at?->format('Y-m-d H:i:s') ?? 'Never' }}
                            @if ($oltDevice->last_error)
                                | Error: {{ $oltDevice->last_error }}
                            @endif
                        </div>
                    </div>
                    <a class="btn light" href="{{ route('olt-onus.olts.edit', $oltDevice) }}">Edit OLT</a>
                    <form method="post" action="{{ route('olt-onus.olts.save-config', $oltDevice) }}">
                        @csrf
                        <button class="btn light" type="submit">Save OLT Config</button>
                    </form>
                    <form method="post" action="{{ route('olt-onus.olts.refresh', $oltDevice) }}">
                        @csrf
                        <button class="btn secondary" type="submit">Refresh Live Data</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Total ONU</span><strong>{{ $stats['total'] }}</strong></div>
    <div class="card stat"><span class="muted">With Live Power</span><strong>{{ $stats['with_power'] }}</strong></div>
    <div class="card stat"><span class="muted">Weak <= -25 dBm</span><strong>{{ $stats['weak_power'] }}</strong></div>
    <div class="card stat"><span class="muted">Last Poll</span><strong style="font-size:18px">{{ $stats['last_polled_at'] ? \Carbon\Carbon::parse($stats['last_polled_at'])->format('Y-m-d H:i') : 'Never' }}</strong></div>
</div>

<form method="get" class="card" style="margin-bottom:16px">
    <div class="form-grid">
        <div>
            <label for="q">Search</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Name, description, MAC">
        </div>
        <div>
            <label for="olt_device_id">OLT</label>
            <select id="olt_device_id" name="olt_device_id">
                <option value="">All OLTs</option>
                @foreach ($oltDevices as $oltDevice)
                    <option value="{{ $oltDevice->id }}" @selected((string) request('olt_device_id') === (string) $oltDevice->id)>{{ $oltDevice->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="pon_port">PON Port</label>
            <select id="pon_port" name="pon_port">
                <option value="">All PON ports</option>
                @foreach ($ponPorts as $ponPort)
                    <option value="{{ $ponPort }}" @selected((string) request('pon_port') === (string) $ponPort)>PON {{ $ponPort }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="vlan">VLAN</label>
            <input id="vlan" name="vlan" type="number" value="{{ request('vlan') }}" placeholder="21, 101, 1108">
        </div>
        <div>
            <label for="power">Power Filter</label>
            <select id="power" name="power">
                <option value="">All</option>
                <option value="weak" @selected(request('power') === 'weak')>Weak <= -25 dBm</option>
                <option value="missing" @selected(request('power') === 'missing')>Missing power note</option>
            </select>
        </div>
    </div>
    <div class="actions" style="margin-top:14px">
        <button class="btn" type="submit">Filter</button>
        <a class="btn light" href="{{ route('olt-onus.index') }}">Clear</a>
    </div>
</form>

@include('partials.per_page')

@php
    $sortUrl = fn (string $key) => route('olt-onus.index', array_merge(
        request()->except('page'),
        [
            'sort' => $key,
            'direction' => request('sort', 'pon_onu') === $key && request('direction', 'asc') === 'asc' ? 'desc' : 'asc',
        ]
    ));
    $sortMark = fn (string $key) => request('sort', 'pon_onu') === $key ? (request('direction', 'asc') === 'asc' ? ' ^' : ' v') : '';
@endphp

<table>
    <thead>
        <tr>
            <th><a href="{{ $sortUrl('pon_onu') }}">PON/ONU{{ $sortMark('pon_onu') }}</a></th>
            <th><a href="{{ $sortUrl('olt') }}">OLT{{ $sortMark('olt') }}</a></th>
            <th><a href="{{ $sortUrl('name') }}">Name{{ $sortMark('name') }}</a></th>
            <th><a href="{{ $sortUrl('mac') }}">MAC{{ $sortMark('mac') }}</a></th>
            <th><a href="{{ $sortUrl('device_macs') }}">Device MACs{{ $sortMark('device_macs') }}</a></th>
            <th><a href="{{ $sortUrl('type') }}">Type{{ $sortMark('type') }}</a></th>
            <th><a href="{{ $sortUrl('status') }}">Status{{ $sortMark('status') }}</a></th>
            <th><a href="{{ $sortUrl('power') }}">Power{{ $sortMark('power') }}</a></th>
            <th><a href="{{ $sortUrl('vlans') }}">VLANs{{ $sortMark('vlans') }}</a></th>
            <th><a href="{{ $sortUrl('last_register') }}">Last Register{{ $sortMark('last_register') }}</a></th>
            <th><a href="{{ $sortUrl('last_deregister') }}">Last Deregister{{ $sortMark('last_deregister') }}</a></th>
            <th><a href="{{ $sortUrl('last_poll') }}">Last Poll{{ $sortMark('last_poll') }}</a></th>
            <th><a href="{{ $sortUrl('description') }}">Description{{ $sortMark('description') }}</a></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($onus as $onu)
            <tr>
                <td><strong>{{ $onu->pon_port }}/{{ $onu->onu_id }}</strong></td>
                <td>{{ $onu->oltDevice?->name ?? $onu->olt_name ?? 'N/A' }}</td>
                <td>{{ $onu->name ?: 'N/A' }}</td>
                <td>{{ $onu->mac_address ?: 'N/A' }}</td>
                <td>
                    @forelse (($onu->learned_macs ?? []) as $learnedMac)
                        <div>
                            <span class="badge">{{ $learnedMac['mac'] ?? '?' }}</span>
                            @if (isset($learnedMac['vlan']))
                                <span class="muted">VLAN {{ $learnedMac['vlan'] }}</span>
                            @endif
                        </div>
                    @empty
                        <span class="muted">No learned MAC</span>
                    @endforelse
                </td>
                <td>{{ $onu->onu_type ?: 'N/A' }}</td>
                <td><span class="badge {{ in_array($onu->status, ['online', 'active'], true) ? 'active' : ($onu->status ? 'pending' : 'inactive') }}">{{ $onu->status ?: 'unknown' }}</span></td>
                <td>
                    @if ($onu->rx_power_dbm !== null)
                        <span class="badge {{ $onu->rx_power_dbm <= -25 ? 'failed' : 'active' }}">{{ number_format((float) $onu->rx_power_dbm, 2) }} dBm</span>
                    @else
                        <span class="muted">No live power</span>
                    @endif
                </td>
                @php
                    $firstVlan = collect($onu->port_vlans ?? [])->first(fn ($vlan) => array_key_exists('vlan', $vlan) && $vlan['vlan'] !== null);
                    $currentVlan = $firstVlan['vlan'] ?? '';
                @endphp
                <td class="vlan-edit-cell" data-vlan-cell>
                    @forelse (($onu->port_vlans ?? []) as $vlan)
                        <span class="badge">
                            {{ $vlan['port'] ?? '?' }}:
                            {{ array_key_exists('vlan', $vlan) && $vlan['vlan'] !== null ? $vlan['vlan'] : ($vlan['mode'] ?? '?') }}
                        </span>
                    @empty
                        <span class="muted">No VLAN config</span>
                    @endforelse
                    <form class="vlan-inline-form" method="post" action="{{ route('olt-onus.vlan.update', $onu) }}" style="display:none; margin-top:8px">
                        @csrf
                        @method('PATCH')
                        <div class="actions" style="gap:6px; flex-wrap:nowrap">
                            <input name="vlan" type="number" min="1" max="4094" value="{{ $currentVlan }}" placeholder="VLAN" style="width:96px; padding:7px" required>
                            <button class="btn secondary" type="submit" style="min-height:32px; padding:7px 9px">Write OLT</button>
                            <button class="btn light" type="button" data-vlan-cancel style="min-height:32px; padding:7px 9px">Cancel</button>
                        </div>
                    </form>
                </td>
                <td>{{ $onu->last_registered_at?->format('Y-m-d H:i:s') ?? 'Never' }}</td>
                <td>
                    @if ($onu->last_deregistered_at)
                        <div>{{ $onu->last_deregistered_at->format('Y-m-d H:i:s') }}</div>
                        <div class="muted">{{ $onu->last_deregister_reason ?: 'No reason' }}</div>
                    @elseif ($onu->last_deregister_reason)
                        <div class="muted">No time</div>
                        <div class="muted">{{ $onu->last_deregister_reason }}</div>
                    @else
                        <span class="muted">Never</span>
                    @endif
                </td>
                <td>{{ $onu->last_live_polled_at?->format('Y-m-d H:i:s') ?? 'Never' }}</td>
                <td>{{ $onu->description ?: 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="13">No live ONU data yet. Add an OLT and refresh live data.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $onus->links() }}</div>
<script>
document.addEventListener('dblclick', function (event) {
    const cell = event.target.closest('[data-vlan-cell]');

    if (! cell || event.target.closest('form, input, button, a')) {
        return;
    }

    document.querySelectorAll('.vlan-inline-form').forEach(function (form) {
        form.style.display = 'none';
    });

    const form = cell.querySelector('.vlan-inline-form');

    if (form) {
        form.style.display = 'block';
        form.querySelector('input[name="vlan"]')?.focus();
        form.querySelector('input[name="vlan"]')?.select();
    }
});

document.addEventListener('click', function (event) {
    const cancel = event.target.closest('[data-vlan-cancel]');

    if (! cancel) {
        return;
    }

    cancel.closest('form').style.display = 'none';
});
</script>
@endsection
