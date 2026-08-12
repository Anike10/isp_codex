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

<div
    id="onu-detail-panel"
    data-refresh-url="{{ route('olt-onus.refresh', $oltOnu) }}"
    data-note-url="{{ route('olt-onus.note.update', $oltOnu) }}"
    class="grid"
    style="gap:16px"
>
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
                <strong>{{ $oltOnu->displayName() ?: 'N/A' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Serial / MAC</span>
                <strong data-field="mac_address">{{ $oltOnu->mac_address ?: 'N/A' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Status</span>
                <strong data-field="status">{{ $oltOnu->status ?: 'unknown' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Last Live Poll</span>
                <strong data-field="last_live_polled_at">{{ $oltOnu->last_live_polled_at?->format('d/m/Y H:i:s') ?? 'Never' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Power</span>
                <div data-field="power_cell">
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
            <div class="summary-item">
                <span class="muted">Auto Update</span>
                <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                    <input id="auto-refresh-onu" type="checkbox">
                    <span>Enable</span>
                </label>
                <select id="auto-refresh-seconds" style="margin-top:8px; max-width:160px">
                    <option value="10">10 seconds</option>
                    <option value="30" selected>30 seconds</option>
                    <option value="60">1 minute</option>
                    <option value="300">5 minutes</option>
                </select>
                <button id="ajax-refresh-onu" class="btn secondary" type="button" style="margin-top:8px">Update Now</button>
                <div class="muted" data-field="refresh_message" style="margin-top:6px"></div>
            </div>
            <div class="summary-item" style="grid-column: span 3;">
                <span class="muted">Description</span>
                <div style="padding:12px 14px; background:rgba(0,0,0,.03); border-radius:8px; min-height:48px;">
                    {{ $oltOnu->description ?: 'N/A' }}
                </div>
            </div>
            <div class="summary-item">
                <span class="muted">Last Registered</span>
                <strong>{{ $oltOnu->last_registered_at?->format('d/m/Y H:i:s') ?? 'Never' }}</strong>
            </div>
            <div class="summary-item">
                <span class="muted">Last Deregistered</span>
                <div>
                    @if ($oltOnu->last_deregistered_at)
                        <strong>{{ $oltOnu->last_deregistered_at->format('d/m/Y H:i:s') }}</strong>
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
        <div data-field="vlans_html_detail">
            @forelse ($oltOnu->port_vlans ?? [] as $vlan)
                <div class="badge">Port {{ $vlan['port'] ?? '?' }}: {{ $vlan['vlan'] ?? ($vlan['mode'] ?? '?') }}</div>
            @empty
                <div class="muted">No VLAN configuration available.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2>Note</h2>
        <textarea data-field="note" rows="4" placeholder="Write ONU note" style="width:100%; padding:10px">{{ $oltOnu->note }}</textarea>
        <button id="save-onu-note" class="btn light" type="button" style="margin-top:10px">Save Note</button>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <h2>Learned MACs</h2>
    <div data-field="learned_macs_html_detail">
        @php
            $displayLearnedMacs = $oltOnu->learned_macs ?? [];
            $displayVlans = collect($oltOnu->port_vlans ?? [])
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
</div>

<div class="card" style="margin-top:16px">
    <h2>Raw Live Output</h2>
    @php
        $rawLiveOutput = (string) ($oltOnu->raw_live_output ?: '');
        $rawLivePreviewLimit = 20000;
        $rawLiveWasTrimmed = mb_strlen($rawLiveOutput) > $rawLivePreviewLimit;
        $rawLivePreview = $rawLiveWasTrimmed ? mb_substr($rawLiveOutput, -$rawLivePreviewLimit) : $rawLiveOutput;
    @endphp
    @if ($rawLiveWasTrimmed)
        <div class="muted" style="margin-bottom:10px">Showing last {{ number_format($rawLivePreviewLimit) }} characters of {{ number_format(mb_strlen($rawLiveOutput)) }}. Use latest refresh output for full diagnostics.</div>
    @endif
    <pre style="white-space:pre-wrap; overflow:auto; margin:0; max-height:520px">{{ $rawLivePreview ?: 'No live output recorded yet.' }}</pre>
</div>

<div class="card" style="margin-top:16px">
    <h2>Raw Interface Config</h2>
    @php
        $rawInterfaceConfig = (string) ($oltOnu->raw_interface_config ?: '');
        $rawConfigPreviewLimit = 20000;
        $rawConfigWasTrimmed = mb_strlen($rawInterfaceConfig) > $rawConfigPreviewLimit;
        $rawConfigPreview = $rawConfigWasTrimmed ? mb_substr($rawInterfaceConfig, -$rawConfigPreviewLimit) : $rawInterfaceConfig;
    @endphp
    @if ($rawConfigWasTrimmed)
        <div class="muted" style="margin-bottom:10px">Showing last {{ number_format($rawConfigPreviewLimit) }} characters of {{ number_format(mb_strlen($rawInterfaceConfig)) }}.</div>
    @endif
    <pre style="white-space:pre-wrap; overflow:auto; margin:0; max-height:520px">{{ $rawConfigPreview ?: 'No interface config recorded yet.' }}</pre>
</div>

<script>
const onuDetailCsrfToken = @json(csrf_token());
const onuPanel = document.getElementById('onu-detail-panel');
const refreshButton = document.getElementById('ajax-refresh-onu');
const autoRefresh = document.getElementById('auto-refresh-onu');
const autoRefreshSeconds = document.getElementById('auto-refresh-seconds');
const saveNoteButton = document.getElementById('save-onu-note');
let onuRefreshTimer = null;

async function onuJsonRequest(url, method, body = null) {
    const options = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': onuDetailCsrfToken,
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

function updateDetail(onu) {
    onuPanel.querySelector('[data-field="mac_address"]').textContent = onu.mac_address;
    onuPanel.querySelector('[data-field="status"]').textContent = onu.status;
    onuPanel.querySelector('[data-field="last_live_polled_at"]').textContent = onu.last_live_polled_at;
    onuPanel.querySelector('[data-field="note"]').value = onu.note || '';

    onuPanel.querySelector('[data-field="power_cell"]').innerHTML = onu.power_badge_class
        ? `<span class="badge ${onu.power_badge_class}" style="font-size:1rem; padding:10px 12px; display:inline-block; min-width:120px; text-align:center;">${onu.rx_power_dbm}</span>`
        : '<span class="muted">No live power</span>';

    document.querySelector('[data-field="vlans_html_detail"]').innerHTML = onu.vlans_html;
    document.querySelector('[data-field="learned_macs_html_detail"]').innerHTML = onu.learned_macs_html;
}

async function refreshOnuDetail() {
    const message = onuPanel.querySelector('[data-field="refresh_message"]');
    refreshButton.disabled = true;
    refreshButton.textContent = 'Updating...';
    message.textContent = '';

    try {
        const data = await onuJsonRequest(onuPanel.dataset.refreshUrl, 'POST');
        updateDetail(data.onu);
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

function scheduleAutoRefresh() {
    clearInterval(onuRefreshTimer);
    onuRefreshTimer = null;

    if (autoRefresh.checked) {
        refreshOnuDetail();
        onuRefreshTimer = setInterval(refreshOnuDetail, Number(autoRefreshSeconds.value) * 1000);
    }
}

refreshButton.addEventListener('click', refreshOnuDetail);
autoRefresh.addEventListener('change', scheduleAutoRefresh);
autoRefreshSeconds.addEventListener('change', scheduleAutoRefresh);

saveNoteButton.addEventListener('click', async function () {
    const message = onuPanel.querySelector('[data-field="refresh_message"]');
    saveNoteButton.disabled = true;
    saveNoteButton.textContent = 'Saving...';

    try {
        const data = await onuJsonRequest(onuPanel.dataset.noteUrl, 'PATCH', {
            note: onuPanel.querySelector('[data-field="note"]').value,
        });
        updateDetail(data.onu);
        message.textContent = 'Note saved';
    } catch (error) {
        message.textContent = error.message;
    } finally {
        saveNoteButton.disabled = false;
        setTimeout(function () {
            saveNoteButton.textContent = 'Save Note';
        }, 900);
    }
});
</script>
@endsection
