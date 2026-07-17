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

<form method="get" class="card" style="margin-bottom:16px">
    <div class="form-grid">
        <div>
            <label for="olt_device_id">OLT</label>
            <select id="olt_device_id" name="olt_device_id" onchange="this.form.submit()">
                <option value="" @selected($showingAllOlts)>All active OLTs</option>
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

@if ($oltDevices->isEmpty())
    <div class="card">No active OLT found.</div>
@else
    <div class="card" style="margin-bottom:16px">
        <h2>{{ $showingAllOlts ? 'All Active OLTs' : $selectedOlt->name }}</h2>
        @if ($showingAllOlts)
            <div class="muted">{{ $oltDevices->count() }} active OLT(s) shown serially.</div>
            @if ($type === 'discovery')
                <div class="actions" style="margin-top:14px">
                    @foreach ($oltDevices as $oltDevice)
                        <form method="post" action="{{ route('olt-onus.olts.refresh-auto-discovery', $oltDevice) }}">
                            @csrf
                            <button class="btn secondary" type="submit">Refresh {{ $oltDevice->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        @else
            <div class="muted">{{ $selectedOlt->host }}:{{ $selectedOlt->port }} | {{ $selectedOlt->brand }} | {{ $selectedOlt->protocol_profile }}</div>
            @if ($type === 'discovery')
                <form method="post" action="{{ route('olt-onus.olts.refresh-auto-discovery', $selectedOlt) }}" style="margin-top:14px">
                    @csrf
                    <button class="btn secondary" type="submit">Refresh Live Data</button>
                </form>
            @endif
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @if ($showingAllOlts)
                    <th>OLT</th>
                @endif
                <th>PON</th>
                <th>ONU ID</th>
                <th>Serial / MAC</th>
                <th>Status</th>
                <th>Raw</th>
                @if (in_array($type, ['discovery', 'deny'], true))
                    <th>{{ $type === 'deny' ? 'Actions' : 'Add' }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @if ($showingAllOlts)
                        <td>{{ $row['olt_name'] }}</td>
                    @endif
                    <td>{{ $row['pon_port'] }}</td>
                    <td>{{ $row['source_onu_id'] ?? 'N/A' }} @if ($type === 'discovery')<span class="muted">add as {{ $row['onu_id'] }}</span>@endif</td>
                    <td>{{ $row['serial'] }}</td>
                    <td><span class="badge {{ $type === 'deny' ? 'failed' : 'pending' }}">{{ $row['status'] }}</span></td>
                    <td>{{ $row['raw'] }}</td>
                    @if (in_array($type, ['discovery', 'deny'], true))
                        <td>
                            <form autocomplete="off" method="post" action="{{ route('olt-onus.auto-discovery.add') }}">
                                @csrf
                                <input type="text" name="fakeuser" autocomplete="username" style="display:none;">
                                <input type="password" name="fakepass" autocomplete="new-password" style="display:none;">
                                <input type="hidden" name="source_type" value="{{ $type }}">
                                <input type="hidden" name="olt_device_id" value="{{ $row['olt_device_id'] }}">
                                <input type="hidden" name="pon_port" value="{{ $row['pon_port'] }}">
                                <input type="hidden" name="serial" value="{{ $row['serial'] }}">
                                <div style="display:grid; gap:8px; min-width:240px">
                                    @if ($row['source_onu_id'] !== null)
                                        <label style="display:flex; flex-direction:column; gap:4px; font-size:0.9em;">
                                            ONU ID
                                            <input autocomplete="off" name="onu_id" type="number" min="{{ ($row['olt_protocol_profile'] ?? null) === 'hsgq_epon' ? 0 : 1 }}" max="256" value="{{ $row['onu_id'] }}" required>
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
                                        Description
                                        <input autocomplete="off" type="text" name="description" value="{{ old('description') }}" placeholder="Description">
                                    </label>
                                    <label style="display:flex; flex-direction:column; gap:4px; font-size:0.9em;">
                                        Ethernet Port
                                        <input autocomplete="off" name="ethernet_port" type="number" min="1" max="8" value="1" required>
                                    </label>
                                    <button class="btn secondary" type="submit">{{ $type === 'deny' ? 'Allow ONU' : 'Add ONU' }}</button>
                                </div>
                            </form>
                            @if ($type === 'deny' && ($row['olt_protocol_profile'] ?? null) === 'hsgq_epon')
                                <form method="post" action="{{ route('olt-onus.deny-list.destroy') }}" onsubmit="return confirm('Delete deny entry {{ addslashes($row['serial']) }} from PON {{ $row['pon_port'] }}? This will not authorize the ONU.')" style="margin-top:8px">
                                    @csrf
                                    @method('delete')
                                    <input type="hidden" name="olt_device_id" value="{{ $row['olt_device_id'] }}">
                                    <input type="hidden" name="pon_port" value="{{ $row['pon_port'] }}">
                                    <input type="hidden" name="serial" value="{{ $row['serial'] }}">
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ (in_array($type, ['discovery', 'deny'], true) ? 6 : 5) + ($showingAllOlts ? 1 : 0) }}">No parsed rows found. Check raw command output below.</td></tr>
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

    @if ($type === 'discovery' && ! $showingAllOlts)
        <div class="card" style="margin-top:16px">
            <h2>Manual Add ONU</h2>
            <form autocomplete="off" method="post" action="{{ route('olt-onus.auto-discovery.add') }}">
                @csrf
                <input type="text" name="fakeuser" autocomplete="username" style="display:none;">
                <input type="password" name="fakepass" autocomplete="new-password" style="display:none;">
                <input type="hidden" name="source_type" value="manual">
                <input type="hidden" name="olt_device_id" value="{{ $selectedOlt->id }}">
                <div class="form-grid">
                    <div><label>PON Port</label><input autocomplete="off" name="pon_port" type="number" min="1" max="16" value="{{ old('pon_port', 1) }}" required></div>
                    <div><label>ONU ID</label><input autocomplete="off" name="onu_id" type="number" min="{{ $selectedOlt->protocol_profile === 'hsgq_epon' ? 0 : 1 }}" max="256" value="{{ old('onu_id', $nextOnuId) }}" required></div>
                    <div><label>Serial / MAC</label><input autocomplete="off" name="serial" value="{{ old('serial') }}" required></div>
                    <div><label>ONU Name</label><input autocomplete="new-name" type="text" name="name" value="{{ old('name') }}" required></div>
                    <div><label>VLAN</label><input autocomplete="off" name="vlan" type="number" min="1" max="4094" value="{{ old('vlan') }}" required></div>
                    <div><label>Description</label><input autocomplete="off" name="description" type="text" value="{{ old('description') }}"></div>
                    <div><label>Ethernet Port</label><input autocomplete="off" name="ethernet_port" type="number" min="1" max="8" value="{{ old('ethernet_port', 1) }}" required></div>
                </div>
                <div class="actions" style="margin-top:14px"><button class="btn secondary" type="submit">Add ONU to OLT</button></div>
            </form>
        </div>
    @endif
@endif
@endsection
