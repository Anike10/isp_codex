@extends('layouts.app')

@section('main_class', 'olt-onus-wide')

@section('content')
<form method="post" action="{{ route('olt-onus.notes.current-laser.store') }}" onsubmit="return confirm('Append the current displayed laser power with today\'s date and time to every ONU/ONT note?')" style="margin-bottom:12px">
    @csrf
    <button class="btn secondary" type="submit">Save All Current Lasers to Notes</button>
</form>

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
                @php
                    $ponSummary = $oltPonSummaries->get($oltDevice->id, collect());
                    $commandWarnings = $oltCommandWarnings[$oltDevice->id] ?? [];
                    if ($oltDevice->status !== 'active') {
                        $connectionBadge = 'Polling disabled';
                        $connectionBadgeClass = 'inactive';
                        $connectionMessage = 'This OLT is configured inactive, so no connection attempt will run.';
                    } elseif ($oltDevice->last_error) {
                        $connectionBadgeClass = 'failed';
                        $connectionError = (string) $oltDevice->last_error;

                        if (preg_match('/(?:authentication|login failed|credential)/i', $connectionError)) {
                            $connectionBadge = 'Login failed';
                            $connectionMessage = 'Check OLT IP/Port, Username, and Password. Edit OLT if needed, save, then run Fast Status Refresh.';
                        } elseif (preg_match('/connection refused/i', $connectionError)) {
                            $connectionBadge = 'Connection refused';
                            $connectionMessage = 'OLT refused login. Verify OLT IP/Port and whether SSH/Telnet service is enabled.';
                        } elseif (preg_match('/(?:timed? out|timeout)/i', $connectionError)) {
                            $connectionBadge = 'Connection timeout';
                            $connectionMessage = 'Connection timeout while connecting to OLT. Check Network, IP, Port, and OLT access method.';
                        } elseif (preg_match('/(?:cannot connect|connection (?:failed|reset)|socket|host unreachable|network is unreachable|no route to host|not connected|broken pipe)/i', $connectionError)) {
                            $connectionBadge = 'Connection failed';
                            $connectionMessage = 'Unable to connect to OLT IP/Port. Verify Network, IP, Port, and access method. Detail: ' . $connectionError;
                        } else {
                            $connectionBadge = 'Refresh/action failed';
                            $connectionMessage = 'OLT connection failed while polling. Detail: ' . $connectionError;
                        }
                    } elseif ($oltDevice->last_polled_at) {
                        $connectionBadge = 'Connected';
                        $connectionBadgeClass = 'active';
                        $connectionMessage = 'Last live OLT connection succeeded.';
                    } else {
                        $connectionBadge = 'Not connected';
                        $connectionBadgeClass = 'pending';
                        $connectionMessage = 'No successful OLT connection has been completed yet.';
                    }
                @endphp
                <div style="margin:12px 0; border:1px solid var(--line); border-radius:14px; padding:14px; background:rgba(0,0,0,0.02)">
                    <div class="actions" style="justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap">
                        <div style="min-width:0; flex:1 1 430px">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap">
                                <h3 style="margin:0; font-size:22px; line-height:1.2">{{ $oltDevice->name }}</h3>
                                <span class="badge {{ $oltDevice->status === 'active' ? 'active' : 'inactive' }}">Configured {{ ucfirst($oltDevice->status) }}</span>
                            </div>
                            <div class="muted" style="margin-top:6px; line-height:1.55; display:flex; flex-wrap:wrap; gap:8px 14px">
                                <span>IP: {{ $oltDevice->host }}:{{ $oltDevice->port }}</span>
                                <span>Brand: {{ $oltDevice->brand ?: 'Unknown Brand' }}</span>
                                <span>Profile: {{ $protocolProfiles[$oltDevice->protocol_profile] ?? $oltDevice->protocol_profile ?? 'Unknown Profile' }}</span>
                                <span>Last Poll: {{ $oltDevice->last_polled_at?->format('d/m/Y H:i:s') ?? 'Never' }}</span>
                                <span>Cached: {{ number_format($oltDevice->onus_count) }}</span>
                                <span>Online: {{ number_format($oltDevice->online_onus_count) }}</span>
                                <span>
                                    PON data:
                                    @forelse ($ponSummary as $ponRow)
                                        {{ $loop->first ? '' : ', ' }}{{ $ponRow->pon_port }} ({{ $ponRow->total }})
                                    @empty
                                        none
                                    @endforelse
                                </span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:8px">
                                <span class="badge {{ $connectionBadgeClass }}">{{ $connectionBadge }}</span>
                                <span style="color:var(--muted); font-size:14px">{{ $connectionMessage }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($commandWarnings !== [])
                        <div style="margin-top:12px; border:1px dashed rgba(255,100,100,.55); background:rgba(255,100,100,.08); border-radius:10px; padding:10px 12px">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:6px">
                                <strong class="muted">Profile mismatch</strong>
                                <form method="post" action="{{ route('olt-onus.olts.apply-profile-defaults', $oltDevice) }}" onsubmit="return confirm('Replace incompatible polling commands with the selected profile defaults?')">
                                    @csrf
                                    <button class="btn secondary" type="submit">Repair Profile Commands</button>
                                </form>
                            </div>
                            <div style="color:#a63e3e">{{ implode(', ', $commandWarnings) }}</div>
                            <div class="muted" style="margin-top:4px">Repair this before the next refresh.</div>
                        </div>
                    @endif

                    <div style="margin-top:12px; display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
                        <section style="background:rgba(255,255,255,0.55); border:1px solid var(--line); border-radius:10px; padding:10px">
                            <div class="muted" style="font-weight:600; margin-bottom:8px">OLT সেটিংস</div>
                            <div class="actions" style="gap:8px; flex-wrap:wrap">
                                <a class="btn light" href="{{ route('olt-onus.olts.edit', $oltDevice) }}">Edit OLT</a>
                                <form method="post" action="{{ route('olt-onus.olts.save-config', $oltDevice) }}">
                                    @csrf
                                    <button class="btn light" type="submit">Save OLT Config</button>
                                </form>
                                <form method="post" action="{{ route('olt-onus.olts.config-backup', $oltDevice) }}">
                                    @csrf
                                    <button class="btn light" type="submit">Download Config Backup</button>
                                </form>
                            </div>
                        </section>

                        <section style="background:rgba(255,255,255,0.55); border:1px solid var(--line); border-radius:10px; padding:10px">
                            <div class="muted" style="font-weight:600; margin-bottom:8px">রিফ্রেশ অপারেশন</div>
                            <div class="actions" style="gap:8px; flex-wrap:wrap">
                                <form method="post" action="{{ route('olt-onus.olts.refresh', $oltDevice) }}">
                                    @csrf
                                    <button class="btn secondary" type="submit">Fast Status Refresh</button>
                                </form>
                                <form method="post" action="{{ route('olt-onus.olts.refresh', $oltDevice) }}" class="actions" data-background-refresh-form data-progress-target="olt-refresh-progress-{{ $oltDevice->id }}" style="gap:8px; flex-wrap:wrap">
                                    @csrf
                                    <input type="hidden" name="refresh_mode" value="{{ $oltDevice->protocol_profile === 'hsgq_gpon' ? 'full_mac' : 'full' }}">
                                    <select name="pon_port" aria-label="PON port for refresh" style="width:150px">
                                        <option value="">All configured PONs</option>
                                        @foreach (($oltPonPorts[$oltDevice->id] ?? []) as $oltPonPort)
                                            <option value="{{ $oltPonPort }}" @selected((string) request('pon_port') === (string) $oltPonPort && (string) request('olt_device_id') === (string) $oltDevice->id)>
                                                PON {{ $oltPonPort }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn light" type="submit">
                                        Power/VLAN + MAC Refresh
                                    </button>
                                </form>
                            </div>
                        </section>

                        <section style="background:rgba(255,255,255,0.55); border:1px solid var(--line); border-radius:10px; padding:10px">
                            <div class="muted" style="font-weight:600; margin-bottom:8px">রক্ষণাবেক্ষণ</div>
                            <div class="actions" style="gap:8px; flex-wrap:wrap">
                                <form method="post" action="{{ route('olt-onus.olts.cached-onus.destroy', $oltDevice) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn light" type="submit">Clear Refresh Error</button>
                                </form>
                                <form method="post" action="{{ route('olt-onus.olts.destroy', $oltDevice) }}" onsubmit="return confirm('Delete OLT {{ addslashes($oltDevice->name) }} and all of its cached ONU/ONT rows? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete OLT</button>
                                </form>
                            </div>
                        </section>
                    </div>

                    <div style="margin-top:10px">
                        <button class="btn light" type="button" data-olt-help-toggle data-help-target="olt-button-help-{{ $oltDevice->id }}" aria-controls="olt-button-help-{{ $oltDevice->id }}" aria-expanded="false">বাটনের কাজ দেখুন</button>
                    </div>
                    <div id="olt-button-help-{{ $oltDevice->id }}" class="muted" style="margin-top:8px; line-height:1.65; padding:10px 12px; border:1px solid var(--line); border-radius:10px; background:#fff" hidden>
                        <div style="font-weight:600; margin-bottom:6px">বাটনের কাজ</div>
                        <ul style="margin:0; padding-left:18px">
                            <li><strong>Edit OLT</strong> — OLT-এর IP, পোর্ট, লগইন ও PON সেটিং পরিবর্তন করুন।</li>
                            <li><strong>Save OLT Config</strong> — OLT-তে করা পরিবর্তন স্থায়ীভাবে সংরক্ষণ করুন।</li>
                            <li><strong>Download Config Backup</strong> — OLT-এর বর্তমান কনফিগারেশনের ব্যাকআপ ডাউনলোড করুন।</li>
                            <li><strong>Fast Status Refresh</strong> — দ্রুত অনলাইন/অফলাইন স্ট্যাটাস আপডেট করুন।</li>
                            <li><strong>All configured PONs</strong> — সব কনফিগার করা PON একসাথে বেছে নিন।</li>
                            <li><strong>Power/VLAN + MAC Refresh</strong> — পাওয়ার, VLAN ও MAC-সহ পূর্ণ তথ্য ব্যাকগ্রাউন্ডে আপডেট করুন; সময় লাগতে পারে।</li>
                            <li><strong>Clear Refresh Error</strong> — শুধু আগের refresh error/output মুছুন, ONU তালিকা থাকবে।</li>
                            <li><strong>Delete OLT</strong> — এই OLT এবং তার cached ONU/ONT তালিকা স্থায়ীভাবে মুছে ফেলে (ফিরে আনা যাবে না)।</li>
                        </ul>
                    </div>
                </div>
                    @php
                        $activeRefreshRun = $oltDevice->latestRefreshRun?->isActive() ? $oltDevice->latestRefreshRun : null;
                    @endphp
                    <div
                        id="olt-refresh-progress-{{ $oltDevice->id }}"
                        data-refresh-progress
                        data-status-url="{{ $activeRefreshRun ? route('olt-onus.refresh-runs.show', $activeRefreshRun) : '' }}"
                        style="margin-top:12px; {{ $activeRefreshRun ? '' : 'display:none' }}"
                    >
                        <div class="actions" style="justify-content:space-between; margin-bottom:6px">
                            <strong data-progress-message>{{ $activeRefreshRun?->message ?: 'Preparing background refresh' }}</strong>
                            <strong data-progress-percent>{{ $activeRefreshRun?->progress ?? 0 }}%</strong>
                        </div>
                        <div class="olt-progress-track">
                            <div class="olt-progress-fill" data-progress-bar style="width:{{ $activeRefreshRun?->progress ?? 0 }}%"></div>
                        </div>
                        <div class="muted" data-progress-state style="margin-top:5px">{{ $activeRefreshRun ? ucfirst($activeRefreshRun->status) : '' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Total ONU</span><strong>{{ $stats['total'] }}</strong></div>
    <div class="card stat"><span class="muted">With Live Power</span><strong>{{ $stats['with_power'] }}</strong></div>
    <div class="card stat"><span class="muted">Weak <= -25 dBm</span><strong>{{ $stats['weak_power'] }}</strong></div>
    <div class="card stat"><span class="muted">Last Poll</span><strong style="font-size:18px">{{ $stats['last_polled_at'] ? \Carbon\Carbon::parse($stats['last_polled_at'])->format('d/m/Y H:i') : 'Never' }}</strong></div>
</div>
@if (request()->filled('olt_device_id'))
    <div class="muted" style="margin:-6px 0 16px">
        Current scope:
        @if (request()->filled('pon_port'))
            PON {{ (int) request('pon_port') }}
        @else
            all PONs with cached data{{ $ponPorts->isNotEmpty() ? ' â€” PON '. $ponPorts->implode(', ') : '' }}
        @endif
    </div>
@endif

<form method="get" class="card filter-form" style="margin-bottom:16px">
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
            <th>No.</th>
            <th>
                <div>ONU / OLT / Name</div>
                <div class="muted" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:5px">
                    <a href="{{ $sortUrl('pon_onu') }}">PON/ONU{{ $sortMark('pon_onu') }}</a>
                    <a href="{{ $sortUrl('olt') }}">OLT{{ $sortMark('olt') }}</a>
                    <a href="{{ $sortUrl('name') }}">Name{{ $sortMark('name') }}</a>
                </div>
            </th>
            <th>Note</th>
            <th>
                <div>Serial / Device MACs</div>
                <div class="muted" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:5px">
                    <a href="{{ $sortUrl('serial') }}">Serial / MAC{{ $sortMark('serial') }}</a>
                    <a href="{{ $sortUrl('device_macs') }}">Device MACs{{ $sortMark('device_macs') }}</a>
                </div>
            </th>
            <th>
                <div>Power / Status</div>
                <div class="muted" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:5px">
                    <a href="{{ $sortUrl('power') }}">Power{{ $sortMark('power') }}</a>
                    <a href="{{ $sortUrl('status') }}">Online/Offline{{ $sortMark('status') }}</a>
                </div>
            </th>
            <th>Ethernet Port</th>
            <th><a href="{{ $sortUrl('vlans') }}">VLANs{{ $sortMark('vlans') }}</a></th>
            <th><a href="{{ $sortUrl('last_register') }}">Last Register{{ $sortMark('last_register') }}</a></th>
            <th><a href="{{ $sortUrl('last_deregister') }}">Last Deregister{{ $sortMark('last_deregister') }}</a></th>
            <th><a href="{{ $sortUrl('last_poll') }}">Last Poll{{ $sortMark('last_poll') }}</a></th>
            <th><a href="{{ $sortUrl('description') }}">Description{{ $sortMark('description') }}</a></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($onus as $onu)
            <tr
                id="onu-row-{{ $onu->id }}"
                data-onu-row
                data-refresh-url="{{ route('olt-onus.refresh', $onu) }}"
                data-note-url="{{ route('olt-onus.note.update', $onu) }}"
                data-laser-note-url="{{ route('olt-onus.note.current-laser.store', $onu) }}"
                style="cursor:auto;"
            >
                <td><strong>{{ $onus->firstItem() + $loop->iteration - 1 }}</strong></td>
                @php
                    $displayOnuName = $onu->displayName();
                @endphp
                <td class="name-edit-cell" data-name-cell data-onu-id="{{ $onu->id }}" data-onu-name="{{ $displayOnuName }}" style="min-width:380px">
                    <div data-onu-click="{{ route('olt-onus.show', $onu) }}" style="cursor:pointer">
                        <span class="muted">PON/ONU:</span>
                        <strong>{{ $onu->pon_port }}/{{ $onu->onu_id }}</strong>
                    </div>
                    <div style="margin-top:5px">
                        <span class="muted">OLT:</span>
                        {{ $onu->oltDevice?->name ?? $onu->olt_name ?? 'N/A' }}
                    </div>
                    <div style="margin-top:5px">
                        <span class="muted">Name:</span>
                        <span class="name-display" style="overflow-wrap:anywhere">{{ $displayOnuName ?: 'N/A' }}</span>
                    </div>
                    <form class="name-inline-form" method="post" action="{{ route('olt-onus.name.update', $onu) }}" data-preserve-scroll style="display:none; margin-top:8px">
                        @csrf
                        @method('PATCH')
                        <div class="actions" style="gap:6px; flex-wrap:wrap">
                            <input name="name" type="text" value="{{ $displayOnuName }}" placeholder="ONU/ONT Name" maxlength="255" style="width:420px; max-width:70vw; padding:9px" required>
                            <button class="btn secondary" type="submit" style="min-height:32px; padding:7px 9px">Save</button>
                            <button class="btn light" type="button" data-name-cancel style="min-height:32px; padding:7px 9px">Cancel</button>
                        </div>
                        <div class="muted" style="margin-top:5px">App supports up to 255 characters. If the OLT accepts fewer characters, the saved OLT value will be shown with a warning.</div>
                    </form>
                </td>
                <td>
                    <textarea data-field="note" rows="2" placeholder="Note" style="min-width:180px; padding:7px">{{ $onu->note }}</textarea>
                    <div class="actions" style="gap:6px; margin-top:6px; flex-wrap:nowrap">
                        <button class="btn light" type="button" data-action="save-note" style="min-height:30px; padding:6px 9px">Save</button>
                        <button class="btn secondary" type="button" data-action="save-current-laser" title="Append current laser reading to this ONU note" style="min-height:30px; padding:6px 9px">+leaser</button>
                    </div>
                </td>
                <td>
                    <div>
                        <span class="muted">Serial / MAC:</span>
                        <span data-field="mac_address">{{ $onu->mac_address ?: 'N/A' }}</span>
                    </div>
                    <div style="margin-top:7px">
                    <div class="muted" style="margin-bottom:4px">Device MACs:</div>
                    <div data-field="learned_macs_html">
                    @php
                        $displayLearnedMacs = $onu->learned_macs ?? [];
                        $displayVlans = collect($onu->port_vlans ?? [])
                            ->pluck('vlan')
                            ->filter(fn ($vlan) => $vlan !== null && $vlan !== '')
                            ->unique()
                            ->values();

                        if (count($displayLearnedMacs) === 1 && $displayVlans->count() > 1 && ! empty($displayLearnedMacs[0]['mac'])) {
                            $displayLearnedMacs = $displayVlans
                                ->map(function ($vlan) use ($displayLearnedMacs) {
                                    $entry = $displayLearnedMacs[0];
                                    $entry['vlan'] = (int) $vlan;

                                    return $entry;
                                })
                                ->all();
                        }
                    @endphp
                    @forelse ($displayLearnedMacs as $learnedMac)
                        <div>
                            <span class="badge">{{ $learnedMac['mac'] ?? '?' }}</span>
                            @if (isset($learnedMac['vlan']))
                                <span class="muted">VLAN {{ $learnedMac['vlan'] }}</span>
                            @endif
                        </div>
                    @empty
                        <span class="muted">No learned MAC</span>
                    @endforelse
                    </div>
                    </div>
                </td>
                <td>
                    <div class="actions" style="gap:6px; align-items:center; flex-wrap:nowrap">
                        <div data-field="power_cell">
                            @if ($onu->rx_power_dbm !== null)
                                <span class="badge {{ $onu->rx_power_dbm <= -25 ? 'failed' : 'active' }}">{{ number_format((float) $onu->rx_power_dbm, 2) }} dBm</span>
                            @else
                                <span class="muted">No live power</span>
                            @endif
                        </div>
                        <span data-field="status_badge" class="badge {{ in_array($onu->status, ['online', 'active'], true) ? 'active' : ($onu->status ? 'pending' : 'inactive') }}">{{ $onu->status ?: 'unknown' }}</span>
                    </div>
                    <button class="btn secondary" type="button" data-action="refresh-onu" title="Refresh status, optical power, VLAN and learned MAC" style="display:block; margin-top:8px; min-height:30px; padding:6px 9px">Update Now</button>
                    <div class="muted" data-field="refresh_message" style="margin-top:6px"></div>
                </td>
                <td>
                    @php
                        $ethernetPorts = $onu->ethernetPortNumbers();
                        $portAdminStates = collect($ethernetPorts)
                            ->mapWithKeys(fn ($ethernetPort) => [(string) $ethernetPort => $onu->ethernetPortState($ethernetPort)])
                            ->all();
                    @endphp
                    @if ($ethernetPorts === [])
                        <span class="muted">No controllable Ethernet port</span>
                    @else
                    <form
                        method="post"
                        action="{{ route('olt-onus.ethernet-port-state.update', $onu) }}"
                        data-port-control-form
                        data-port-states='@json($portAdminStates)'
                        data-preserve-scroll
                        style="min-width:185px"
                    >
                        @csrf
                        @method('PATCH')
                        <label class="muted" for="ethernet-port-{{ $onu->id }}">Select Ethernet port</label>
                        <div style="margin-top:5px">
                            <select id="ethernet-port-{{ $onu->id }}" name="ethernet_port" data-port-state-select style="min-width:145px; padding:7px">
                                @foreach ($ethernetPorts as $ethernetPort)
                                    @php
                                        $portState = $onu->ethernetPortState($ethernetPort);
                                        $portIsEnabled = $portState === 'enabled';
                                    @endphp
                                    <option
                                        value="{{ $ethernetPort }}"
                                        style="background:{{ $portIsEnabled ? '#dcfce7' : '#fee2e2' }}; color:{{ $portIsEnabled ? '#166534' : '#991b1b' }}"
                                    >Port {{ $ethernetPort }} â€” {{ $portIsEnabled ? 'Enabled' : 'Disabled' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn secondary" name="state" value="enable" type="submit" data-port-toggle style="margin-top:7px; min-height:30px; padding:6px 10px">Enable</button>
                        <div class="muted" data-port-state-label style="margin-top:5px">Current state: Enabled</div>
                    </form>
                    @endif
                </td>
                @php
                    $onuCurrentVlan = data_get(
                        collect($onu->port_vlans ?? [])->first(fn ($vlan) => array_key_exists('vlan', $vlan) && $vlan['vlan'] !== null),
                        'vlan',
                        ''
                    );
                    $onuCurrentVlanMode = collect($onu->port_vlans ?? [])->contains(
                        fn ($vlan) => strtolower((string) ($vlan['mode'] ?? '')) === 'transparent'
                    ) ? 'transparent' : 'tag';
                @endphp
                <td class="vlan-edit-cell" data-vlan-cell>
                    <div data-field="vlans_html">
                        @forelse (($onu->port_vlans ?? []) as $vlan)
                            <span class="badge">
                                {{ $vlan['port'] ?? '?' }}:
                                {{ array_key_exists('vlan', $vlan) && $vlan['vlan'] !== null ? $vlan['vlan'] : ($vlan['mode'] ?? '?') }}
                            </span>
                        @empty
                            <span class="muted">No VLAN config</span>
                        @endforelse
                    </div>
                    <form class="vlan-inline-form" method="post" action="{{ route('olt-onus.vlan.update', $onu) }}" style="display:none; margin-top:8px">
                        @csrf
                        @method('PATCH')
                        <div class="actions" style="gap:6px; align-items:flex-end">
                            <label style="display:grid; gap:3px; font-size:12px">
                                Mode
                                <select name="mode" data-vlan-mode style="width:118px; padding:7px">
                                    <option value="tag" @selected($onuCurrentVlanMode === 'tag')>Tagged VLAN</option>
                                    @if ($onu->oltDevice?->protocol_profile === 'hsgq_epon')
                                        <option value="transparent" @selected($onuCurrentVlanMode === 'transparent')>Transparent</option>
                                    @endif
                                </select>
                            </label>
                            <label data-vlan-value-wrap style="display:grid; gap:3px; font-size:12px">
                                VLAN
                                <input name="vlan" data-vlan-value type="number" min="1" max="4094" value="{{ $onuCurrentVlan }}" placeholder="VLAN" style="width:96px; padding:7px">
                            </label>
                            <button class="btn secondary" type="submit" style="min-height:32px; padding:7px 9px">Write OLT</button>
                            <button class="btn light" type="button" data-vlan-cancel style="min-height:32px; padding:7px 9px">Cancel</button>
                        </div>
                    </form>
                </td>
                <td>{{ $onu->last_registered_at?->format('d/m/Y H:i:s') ?? 'Never' }}</td>
                <td>
                    @if ($onu->last_deregistered_at)
                        <div>{{ $onu->last_deregistered_at->format('d/m/Y H:i:s') }}</div>
                        <div class="muted">{{ $onu->last_deregister_reason ?: 'No reason' }}</div>
                    @elseif ($onu->last_deregister_reason)
                        <div class="muted">No time</div>
                        <div class="muted">{{ $onu->last_deregister_reason }}</div>
                    @else
                        <span class="muted">Never</span>
                    @endif
                </td>
                <td data-field="last_live_polled_at">{{ $onu->last_live_polled_at?->format('d/m/Y H:i:s') ?? 'Never' }}</td>
                <td class="desc-edit-cell" data-desc-cell data-onu-id="{{ $onu->id }}" data-onu-desc="{{ $onu->description }}">
                    <span class="desc-display">{{ $onu->description ?: 'N/A' }}</span>
                    <form class="desc-inline-form" method="post" action="{{ route('olt-onus.description.update', $onu) }}" data-preserve-scroll style="display:none; margin-top:8px">
                        @csrf
                        @method('PATCH')
                        <div class="actions" style="gap:6px; flex-wrap:nowrap">
                            <input name="description" type="text" value="{{ $onu->description }}" placeholder="Description" style="width:150px; padding:7px">
                            <button class="btn secondary" type="submit" style="min-height:32px; padding:7px 9px">Save</button>
                            <button class="btn light" type="button" data-desc-cancel style="min-height:32px; padding:7px 9px">Cancel</button>
                        </div>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11">No live ONU data yet. Add an OLT and refresh live data.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $onus->links() }}</div>
<script>
const oltOnuCsrfToken = @json(csrf_token());
const oltOnuScrollStorageKey = 'olt-onus-scroll:' + window.location.pathname + window.location.search;
const oltOnuSavedScroll = sessionStorage.getItem(oltOnuScrollStorageKey);

if (oltOnuSavedScroll !== null) {
    sessionStorage.removeItem(oltOnuScrollStorageKey);
    requestAnimationFrame(() => window.scrollTo(0, Number(oltOnuSavedScroll) || 0));
}

function syncEthernetPortControl(form) {
    const select = form.querySelector('[data-port-state-select]');
    const button = form.querySelector('[data-port-toggle]');
    const label = form.querySelector('[data-port-state-label]');
    const states = JSON.parse(form.dataset.portStates || '{}');
    const state = String(states[select.value] || 'enabled').toLowerCase();
    const isEnabled = ['enable', 'enabled', 'up', 'active'].includes(state);

    select.style.background = isEnabled ? '#dcfce7' : '#fee2e2';
    select.style.color = isEnabled ? '#166534' : '#991b1b';
    select.style.borderColor = isEnabled ? '#86efac' : '#fca5a5';
    label.textContent = 'Current state: ' + (isEnabled ? 'Enabled' : 'Disabled');

    if (isEnabled) {
        button.value = 'disable';
        button.textContent = 'Disable';
        button.classList.remove('secondary');
        button.classList.add('danger');
    } else {
        button.value = 'enable';
        button.textContent = 'Enable';
        button.classList.remove('danger');
        button.classList.add('secondary');
    }
}

document.querySelectorAll('[data-port-control-form]').forEach(syncEthernetPortControl);

document.addEventListener('change', function (event) {
    const select = event.target.closest('[data-port-state-select]');

    if (select) {
        syncEthernetPortControl(select.closest('[data-port-control-form]'));
    }
});

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('[data-port-control-form]');

    if (! form) {
        return;
    }

    event.preventDefault();
    const select = form.querySelector('[data-port-state-select]');
    const button = form.querySelector('[data-port-toggle]');
    const label = form.querySelector('[data-port-state-label]');
    const port = select.value;
    const state = button.value;

    if (state === 'disable') {
        if (! confirm('Disable Ethernet port ' + port + '? Customer traffic on that port will stop.')) {
            return;
        }
    }

    button.disabled = true;
    button.textContent = 'Working...';

    try {
        const data = await oltOnuJsonRequest(form.action, 'PATCH', {
            ethernet_port: Number(port),
            state,
        });
        const states = JSON.parse(form.dataset.portStates || '{}');
        states[String(data.port)] = data.port_state;
        form.dataset.portStates = JSON.stringify(states);
        syncEthernetPortControl(form);
    } catch (error) {
        label.textContent = error.message || 'Port control failed';
        button.textContent = state === 'disable' ? 'Disable' : 'Enable';
    } finally {
        button.disabled = false;
    }
});

async function oltOnuJsonRequest(url, method, body = null) {
    const options = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': oltOnuCsrfToken,
        },
        credentials: 'same-origin',
    };

    if (body !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    const data = await parseOltRefreshResponse(response);

    if (! response.ok) {
        throw new Error(data.message || 'Request failed');
    }

    return data;
}

function setBadge(element, label, badgeClass) {
    element.className = 'badge ' + (badgeClass || '');
    element.textContent = label || 'unknown';
}

function updateOnuRow(row, onu) {
    row.querySelector('[data-field="mac_address"]').textContent = onu.mac_address;
    row.querySelector('[data-field="learned_macs_html"]').innerHTML = onu.learned_macs_html;
    row.querySelector('[data-field="vlans_html"]').innerHTML = onu.vlans_html;
    row.querySelector('[data-field="last_live_polled_at"]').textContent = onu.last_live_polled_at;
    row.querySelector('[data-field="note"]').value = onu.note || '';

    const powerCell = row.querySelector('[data-field="power_cell"]');
    powerCell.innerHTML = onu.power_badge_class
        ? `<span class="badge ${onu.power_badge_class}">${onu.rx_power_dbm}</span>`
        : '<span class="muted">No live power</span>';

    setBadge(row.querySelector('[data-field="status_badge"]'), onu.status, onu.status_badge_class);
}

document.addEventListener('click', async function (event) {
    const refreshButton = event.target.closest('[data-action="refresh-onu"]');
    const noteButton = event.target.closest('[data-action="save-note"]');
    const laserButton = event.target.closest('[data-action="save-current-laser"]');

    if (refreshButton) {
        const row = refreshButton.closest('[data-onu-row]');
        const message = row.querySelector('[data-field="refresh_message"]');
        refreshButton.disabled = true;
        refreshButton.textContent = 'Updating...';
        message.textContent = '';

        try {
            const data = await oltOnuJsonRequest(row.dataset.refreshUrl, 'POST', {
                refresh_mode: 'full_mac',
            });
            updateOnuRow(row, data.onu);
            message.textContent = 'Power, VLAN & MAC updated';
        } catch (error) {
            message.textContent = error.message;
        } finally {
            refreshButton.disabled = false;
            setTimeout(function () {
                refreshButton.textContent = 'Update Now';
            }, 900);
        }
    }

    if (noteButton) {
        const row = noteButton.closest('[data-onu-row]');
        const message = row.querySelector('[data-field="refresh_message"]');
        noteButton.disabled = true;
        noteButton.textContent = 'Saving...';

        try {
            const data = await oltOnuJsonRequest(row.dataset.noteUrl, 'PATCH', {
                note: row.querySelector('[data-field="note"]').value,
            });
            updateOnuRow(row, data.onu);
            message.textContent = 'Note saved';
        } catch (error) {
            message.textContent = error.message;
        } finally {
            noteButton.disabled = false;
            setTimeout(function () {
                noteButton.textContent = 'Save';
            }, 900);
        }
    }

    if (laserButton) {
        const row = laserButton.closest('[data-onu-row]');
        const message = row.querySelector('[data-field="refresh_message"]');
        laserButton.disabled = true;
        laserButton.textContent = 'Saving...';

        try {
            const data = await oltOnuJsonRequest(row.dataset.laserNoteUrl, 'POST');
            updateOnuRow(row, data.onu);
            message.textContent = data.message || 'Current laser saved to note';
        } catch (error) {
            message.textContent = error.message;
        } finally {
            laserButton.disabled = false;
            laserButton.textContent = '+leaser';
        }
    }
});

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.vlan-inline-form');

    if (!form) {
        return;
    }

    event.preventDefault();
    const row = form.closest('[data-onu-row]');
    const message = row.querySelector('[data-field="refresh_message"]');
    const submitButton = form.querySelector('button[type="submit"]');
    const vlanInput = form.querySelector('input[name="vlan"]');
    const modeInput = form.querySelector('[name="mode"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Saving...';
    message.textContent = '';

    try {
        const data = await oltOnuJsonRequest(form.action, 'PATCH', {
            mode: modeInput.value,
            vlan: modeInput.value === 'transparent' ? null : vlanInput.value,
        });
        updateOnuRow(row, data.onu);
        form.style.display = 'none';
        message.textContent = data.message || 'VLAN updated';
    } catch (error) {
        message.textContent = error.message;
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Save';
    }
});

function syncVlanModeForm(form) {
    const mode = form.querySelector('[data-vlan-mode]')?.value || 'tag';
    const input = form.querySelector('[data-vlan-value]');
    const wrapper = form.querySelector('[data-vlan-value-wrap]');
    const transparent = mode === 'transparent';

    input.disabled = transparent;
    input.required = ! transparent;
    wrapper.style.display = transparent ? 'none' : 'grid';
}

document.addEventListener('change', function (event) {
    const mode = event.target.closest('[data-vlan-mode]');

    if (mode) {
        syncVlanModeForm(mode.closest('.vlan-inline-form'));
    }
});

// Handle VLAN cell double-click
document.addEventListener('dblclick', function (event) {
    const cell = event.target.closest('[data-vlan-cell]');

    if (! cell || event.target.closest('form, input, button, a')) {
        return;
    }

    event.stopPropagation();
    event.preventDefault();

    document.querySelectorAll('.vlan-inline-form').forEach(function (form) {
        form.style.display = 'none';
    });

    const form = cell.querySelector('.vlan-inline-form');

    if (form) {
        form.style.display = 'block';
        syncVlanModeForm(form);

        if (form.querySelector('[data-vlan-mode]').value === 'transparent') {
            form.querySelector('[data-vlan-mode]')?.focus();
        } else {
            form.querySelector('input[name="vlan"]')?.focus();
            form.querySelector('input[name="vlan"]')?.select();
        }
    }
});

// Handle Name cell double-click
document.addEventListener('dblclick', function (event) {
    const cell = event.target.closest('[data-name-cell]');

    if (! cell || event.target.closest('form, input, button, a')) {
        return;
    }

    event.stopPropagation();
    event.preventDefault();

    document.querySelectorAll('.name-inline-form').forEach(function (form) {
        form.style.display = 'none';
    });

    const form = cell.querySelector('.name-inline-form');

    if (form) {
        form.style.display = 'block';
        form.querySelector('input[name="name"]')?.focus();
        form.querySelector('input[name="name"]')?.select();
    }
});

// Handle Description cell double-click
document.addEventListener('dblclick', function (event) {
    const cell = event.target.closest('[data-desc-cell]');

    if (! cell || event.target.closest('form, input, button, a')) {
        return;
    }

    event.stopPropagation();
    event.preventDefault();

    document.querySelectorAll('.desc-inline-form').forEach(function (form) {
        form.style.display = 'none';
    });

    const form = cell.querySelector('.desc-inline-form');

    if (form) {
        form.style.display = 'block';
        form.querySelector('input[name="description"]')?.focus();
        form.querySelector('input[name="description"]')?.select();
    }
});

// Handle Cancel buttons
document.addEventListener('click', function (event) {
    const cancel = event.target.closest('[data-vlan-cancel], [data-name-cancel], [data-desc-cancel]');

    if (cancel) {
        cancel.closest('form').style.display = 'none';
        return;
    }
});

// Handle ONU click to navigate to details
document.addEventListener('click', function (event) {
    const cell = event.target.closest('[data-onu-click]');

    if (! cell || event.target.closest('a, button, input, select, textarea, form')) {
        return;
    }

    window.location = cell.dataset.onuClick;
});

function renderOltRefreshProgress(panel, run) {
    const progress = Math.max(0, Math.min(100, Number(run.progress || 0)));
    panel.style.display = 'block';
    panel.querySelector('[data-progress-bar]').style.width = progress + '%';
    panel.querySelector('[data-progress-percent]').textContent = progress + '%';
    panel.querySelector('[data-progress-message]').textContent = run.message || 'Background refresh is running';
    panel.querySelector('[data-progress-state]').textContent = (run.status || 'running').replace(/^./, character => character.toUpperCase());
}

async function parseOltRefreshResponse(response) {
    const text = await response.text();

    try {
        return JSON.parse(text);
    } catch (error) {
        if (response.redirected && response.url.includes('/login')) {
            throw new Error('Login session expired. Reload the page and sign in again.');
        }

        if (text.trim().startsWith('<')) {
            throw new Error(`Server returned an HTML error page (HTTP ${response.status}). Reload this page and try again.`);
        }

        throw new Error(`Invalid server response (HTTP ${response.status}).`);
    }
}

async function pollOltRefreshProgress(panel) {
    const statusUrl = panel.dataset.statusUrl;

    if (!statusUrl) {
        return;
    }

    try {
        const response = await fetch(statusUrl, {
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        });
        const run = await parseOltRefreshResponse(response);

        if (!response.ok) {
            throw new Error(run.message || 'Could not read refresh progress');
        }

        renderOltRefreshProgress(panel, run);

        if (run.status === 'queued' || run.status === 'running') {
            setTimeout(() => pollOltRefreshProgress(panel), 800);
        } else if (run.status === 'completed') {
            setTimeout(() => window.location.reload(), 1200);
        } else {
            const form = document.querySelector(`[data-progress-target="${panel.id}"]`);
            const button = form?.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = false;
                button.textContent = 'Power/VLAN + MAC Refresh';
            }
            setTimeout(() => window.location.reload(), 2500);
        }
    } catch (error) {
        panel.querySelector('[data-progress-message]').textContent = error.message;
        setTimeout(() => pollOltRefreshProgress(panel), 2000);
    }
}

document.querySelectorAll('[data-background-refresh-form]').forEach(function (form) {
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const panel = document.getElementById(form.dataset.progressTarget);
        const submitButton = event.submitter || form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Starting...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin',
                body: new FormData(form),
            });
            const data = await parseOltRefreshResponse(response);

            if (!response.ok) {
                throw new Error(data.message || 'Background refresh could not start');
            }

            panel.dataset.statusUrl = data.run.status_url;
            renderOltRefreshProgress(panel, data.run);
            pollOltRefreshProgress(panel);
        } catch (error) {
            panel.style.display = 'block';
            panel.querySelector('[data-progress-message]').textContent = error.message;
            panel.querySelector('[data-progress-state]').textContent = 'Failed to start';
            submitButton.disabled = false;
            submitButton.textContent = 'Power/VLAN + MAC Refresh';
        }
    });
});

document.querySelectorAll('[data-olt-help-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        const help = document.getElementById(button.dataset.helpTarget);

        if (!help) {
            return;
        }

        help.hidden = !help.hidden;
        button.setAttribute('aria-expanded', help.hidden ? 'false' : 'true');
        button.textContent = help.hidden ? 'বাটনের কাজ দেখুন' : 'বাটনের কাজ লুকান';
    });
});

document.querySelectorAll('[data-refresh-progress][data-status-url]:not([data-status-url=""])').forEach(pollOltRefreshProgress);

document.addEventListener('submit', function (event) {
    if (event.defaultPrevented || ! event.target.closest('[data-preserve-scroll]')) {
        return;
    }

    sessionStorage.setItem(oltOnuScrollStorageKey, String(window.scrollY));
});
</script>
@endsection
