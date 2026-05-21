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
                        <button class="btn secondary" type="submit">Fast Refresh</button>
                    </form>
                    <form method="post" action="{{ route('olt-onus.olts.refresh', $oltDevice) }}">
                        @csrf
                        <input type="hidden" name="refresh_mode" value="full">
                        <select name="pon_port" aria-label="PON port for full refresh" style="width:120px">
                            <option value="">All PONs</option>
                            @foreach (($oltPonPorts[$oltDevice->id] ?? []) as $oltPonPort)
                                <option value="{{ $oltPonPort }}" @selected((string) request('pon_port') === (string) $oltPonPort && (string) request('olt_device_id') === (string) $oltDevice->id)>
                                    PON {{ $oltPonPort }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn light" type="submit">Full Power/VLAN Refresh</button>
                    </form>
                    @if ($oltDevice->protocol_profile === 'hsgq_gpon')
                        <form method="post" action="{{ route('olt-onus.olts.refresh', $oltDevice) }}">
                            @csrf
                            <input type="hidden" name="refresh_mode" value="mac">
                            <select name="pon_port" aria-label="PON port for MAC refresh" style="width:120px">
                                @foreach (($oltPonPorts[$oltDevice->id] ?? []) as $oltPonPort)
                                    <option value="{{ $oltPonPort }}" @selected((string) request('pon_port') === (string) $oltPonPort && (string) request('olt_device_id') === (string) $oltDevice->id)>
                                        PON {{ $oltPonPort }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn light" type="submit">MAC Refresh</button>
                        </form>
                    @endif
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
            <th>No.</th>
            <th><a href="{{ $sortUrl('pon_onu') }}">PON/ONU{{ $sortMark('pon_onu') }}</a></th>
            <th><a href="{{ $sortUrl('olt') }}">OLT{{ $sortMark('olt') }}</a></th>
            <th><a href="{{ $sortUrl('name') }}">Name{{ $sortMark('name') }}</a></th>
            <th>Note</th>
            <th><a href="{{ $sortUrl('serial') }}">Serial / MAC{{ $sortMark('serial') }}</a></th>
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
            <tr
                id="onu-row-{{ $onu->id }}"
                data-onu-row
                data-refresh-url="{{ route('olt-onus.refresh', $onu) }}"
                data-note-url="{{ route('olt-onus.note.update', $onu) }}"
                style="cursor:auto;"
            >
                <td><strong>{{ $onus->firstItem() + $loop->iteration - 1 }}</strong></td>
                <td data-onu-click="{{ route('olt-onus.show', $onu) }}" style="cursor:pointer;"><strong>{{ $onu->pon_port }}/{{ $onu->onu_id }}</strong></td>
                <td>{{ $onu->oltDevice?->name ?? $onu->olt_name ?? 'N/A' }}</td>
                <td class="name-edit-cell" data-name-cell data-onu-id="{{ $onu->id }}" data-onu-name="{{ $onu->name }}">
                    <span class="name-display">{{ $onu->name ?: 'N/A' }}</span>
                    <form class="name-inline-form" method="post" action="{{ route('olt-onus.name.update', $onu) }}" style="display:none; margin-top:8px">
                        @csrf
                        @method('PATCH')
                        <div class="actions" style="gap:6px; flex-wrap:nowrap">
                            <input name="name" type="text" value="{{ $onu->name }}" placeholder="ONU Name" style="width:150px; padding:7px" required>
                            <button class="btn secondary" type="submit" style="min-height:32px; padding:7px 9px">Save</button>
                            <button class="btn light" type="button" data-name-cancel style="min-height:32px; padding:7px 9px">Cancel</button>
                        </div>
                    </form>
                </td>
                <td>
                    <textarea data-field="note" rows="2" placeholder="Note" style="min-width:180px; padding:7px">{{ $onu->note }}</textarea>
                    <button class="btn light" type="button" data-action="save-note" style="margin-top:6px; min-height:30px; padding:6px 9px">Save</button>
                </td>
                <td data-field="mac_address">{{ $onu->mac_address ?: 'N/A' }}</td>
                <td data-field="learned_macs_html">
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
                </td>
                <td>{{ $onu->onu_type ?: 'N/A' }}</td>
                <td>
                    <span data-field="status_badge" class="badge {{ in_array($onu->status, ['online', 'active'], true) ? 'active' : ($onu->status ? 'pending' : 'inactive') }}">{{ $onu->status ?: 'unknown' }}</span>
                    <button class="btn secondary" type="button" data-action="refresh-onu" style="margin-top:8px; min-height:30px; padding:6px 9px">Update Now</button>
                    <div class="muted" data-field="refresh_message" style="margin-top:6px"></div>
                </td>
                <td data-field="power_cell">
                    @if ($onu->rx_power_dbm !== null)
                        <span class="badge {{ $onu->rx_power_dbm <= -25 ? 'failed' : 'active' }}">{{ number_format((float) $onu->rx_power_dbm, 2) }} dBm</span>
                    @else
                        <span class="muted">No live power</span>
                    @endif
                </td>
                @php($onuCurrentVlan = data_get(collect($onu->port_vlans ?? [])->first(fn ($vlan) => array_key_exists('vlan', $vlan) && $vlan['vlan'] !== null), 'vlan', ''))
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
                        <div class="actions" style="gap:6px; flex-wrap:nowrap">
                            <input name="vlan" type="number" min="1" max="4094" value="{{ $onuCurrentVlan }}" placeholder="VLAN" style="width:96px; padding:7px" required>
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
                <td data-field="last_live_polled_at">{{ $onu->last_live_polled_at?->format('Y-m-d H:i:s') ?? 'Never' }}</td>
                <td class="desc-edit-cell" data-desc-cell data-onu-id="{{ $onu->id }}" data-onu-desc="{{ $onu->description }}">
                    <span class="desc-display">{{ $onu->description ?: 'N/A' }}</span>
                    <form class="desc-inline-form" method="post" action="{{ route('olt-onus.description.update', $onu) }}" style="display:none; margin-top:8px">
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
                <td colspan="15">No live ONU data yet. Add an OLT and refresh live data.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $onus->links() }}</div>
<script>
const oltOnuCsrfToken = @json(csrf_token());

async function oltOnuJsonRequest(url, method, body = null) {
    const options = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': oltOnuCsrfToken,
        },
    };

    if (body !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    const data = await response.json();

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

    if (refreshButton) {
        const row = refreshButton.closest('[data-onu-row]');
        const message = row.querySelector('[data-field="refresh_message"]');
        refreshButton.disabled = true;
        refreshButton.textContent = 'Updating...';
        message.textContent = '';

        try {
            const data = await oltOnuJsonRequest(row.dataset.refreshUrl, 'POST');
            updateOnuRow(row, data.onu);
            message.textContent = 'Updated';
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
        form.querySelector('input[name="vlan"]')?.focus();
        form.querySelector('input[name="vlan"]')?.select();
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
</script>
@endsection
