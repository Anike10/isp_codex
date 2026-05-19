@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $title }}</h1>
        <div class="muted">{{ $type === 'deny' ? 'Live ONU/ONT deny list from OLT devices.' : 'Live auto-discovered ONU/ONT list with add/write actions.' }}</div>
    </div>
    <div class="actions">
        <a class="btn light" href="{{ route('olt-onus.index') }}">OLT ONUs</a>
    </div>
</div>

@if (! empty($liveRefreshMessage))
    <div class="card" style="margin-bottom:16px">
        <span class="badge {{ str_contains($liveRefreshMessage, 'failed') ? 'failed' : 'active' }}">{{ $liveRefreshMessage }}</span>
    </div>
@endif

<form method="get" class="card" style="margin-bottom:16px">
    <div class="form-grid">
        <div>
            <label for="olt_device_id">OLT</label>
            <select id="olt_device_id" name="olt_device_id" onchange="this.form.submit()">
                @foreach ($oltDevices as $oltDevice)
                    <option value="{{ $oltDevice->id }}" @selected($selectedOlt?->id === $oltDevice->id)>{{ $oltDevice->name }} - {{ $oltDevice->host }}:{{ $oltDevice->port }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="actions" style="margin-top:14px">
        <button class="btn" type="submit">Refresh List</button>
    </div>
</form>

@if (! $selectedOlt)
    <div class="card">No active OLT found.</div>
@else
    <div class="card" style="margin-bottom:16px">
        <h2>{{ $selectedOlt->name }}</h2>
        <div class="muted">{{ $selectedOlt->host }}:{{ $selectedOlt->port }} | {{ $selectedOlt->brand }} | {{ $selectedOlt->protocol_profile }}</div>
    </div>

    @if ($type === 'discovery')
        <div class="card" style="margin-bottom:16px">
            <h2>Manual Add ONU</h2>
            <form autocomplete="off" method="post" action="{{ route('olt-onus.auto-discovery.add') }}">
                @csrf
                <input type="text" name="fakeuser" autocomplete="username" style="display:none;">
                <input type="password" name="fakepass" autocomplete="new-password" style="display:none;">
                <input type="hidden" name="olt_device_id" value="{{ $selectedOlt->id }}">
                <div class="form-grid">
                    <div><label>PON Port</label><input autocomplete="off" name="pon_port" type="number" min="1" max="16" value="{{ old('pon_port', 1) }}" required></div>
                    <div><label>ONU ID</label><input autocomplete="off" name="onu_id" type="number" min="{{ $selectedOlt->protocol_profile === 'hsgq_gpon' ? 1 : 0 }}" max="256" value="{{ old('onu_id', $nextOnuId) }}" required></div>
                    <div><label>Serial / MAC</label><input autocomplete="off" name="serial" value="{{ old('serial') }}" required></div>
                    <div><label>ONU Name</label><input autocomplete="new-name" type="text" name="name" value="{{ old('name') }}" required></div>
                    <div><label>VLAN</label><input autocomplete="off" name="vlan" type="number" min="1" max="4094" value="{{ old('vlan') }}" required></div>
                    <div><label>Ethernet Port</label><input autocomplete="off" name="ethernet_port" type="number" min="1" max="8" value="{{ old('ethernet_port', 1) }}" required></div>
                </div>
                <div class="actions" style="margin-top:14px"><button class="btn secondary" type="submit">Add ONU to OLT</button></div>
            </form>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>PON</th>
                <th>ONU ID</th>
                <th>Serial / MAC</th>
                <th>Status</th>
                <th>Raw</th>
                @if ($type === 'discovery')
                    <th>Add</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['pon_port'] }}</td>
                    <td>{{ $row['source_onu_id'] ?? '—' }} @if ($type === 'discovery')<span class="muted">add as {{ $row['onu_id'] }}</span>@endif</td>
                    <td>{{ $row['serial'] }}</td>
                    <td><span class="badge {{ $type === 'deny' ? 'failed' : 'pending' }}">{{ $row['status'] }}</span></td>
                    <td>{{ $row['raw'] }}</td>
                    @if ($type === 'discovery')
                        <td>
                            <form autocomplete="off" method="post" action="{{ route('olt-onus.auto-discovery.add') }}">
                                @csrf
                                <input type="text" name="fakeuser" autocomplete="username" style="display:none;">
                                <input type="password" name="fakepass" autocomplete="new-password" style="display:none;">
                                <input type="hidden" name="olt_device_id" value="{{ $row['olt_device_id'] }}">
                                <input type="hidden" name="pon_port" value="{{ $row['pon_port'] }}">
                                <input type="hidden" name="serial" value="{{ $row['serial'] }}">
                                <div style="display:grid; gap:8px; min-width:240px">
                                    @if ($row['source_onu_id'] !== null)
                                        <label style="display:flex; flex-direction:column; gap:4px; font-size:0.9em;">
                                            ONU ID
                                            <input autocomplete="off" name="onu_id" type="number" min="{{ $selectedOlt->protocol_profile === 'hsgq_gpon' ? 1 : 0 }}" max="256" value="{{ $row['onu_id'] }}" required>
                                        </label>
                                    @else
                                        <input type="hidden" name="onu_id" value="{{ $row['onu_id'] }}">
                                        <div style="font-size:0.9em; color:#555;">Suggested ONU ID: {{ $row['onu_id'] }}</div>
                                    @endif
                                    <label style="display:flex; flex-direction:column; gap:4px; font-size:0.9em;">
                                        ONU Name
                                        <input autocomplete="new-name" type="text" name="name" value="{{ old('name') }}" placeholder="ONU Name" required>
                                    </label>
                                    <label style="display:flex; flex-direction:column; gap:4px; font-size:0.9em;">
                                        VLAN
                                        <input autocomplete="off" name="vlan" type="number" min="1" max="4094" placeholder="VLAN" required>
                                    </label>
                                    <label style="display:flex; flex-direction:column; gap:4px; font-size:0.9em;">
                                        Ethernet Port
                                        <input autocomplete="off" name="ethernet_port" type="number" min="1" max="8" value="1" required>
                                    </label>
                                    <button class="btn secondary" type="submit">Add ONU</button>
                                </div>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $type === 'discovery' ? 6 : 5 }}">No parsed rows found. Check raw command output below.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="grid" style="margin-top:16px">
        @foreach ($outputs as $command => $output)
            <div class="card">
                <h2>{{ $command }}</h2>
                <pre style="white-space:pre-wrap; overflow:auto; margin:0">{{ $output }}</pre>
            </div>
        @endforeach
    </div>
@endif
@endsection
