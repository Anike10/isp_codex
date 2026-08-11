@extends('layouts.app')

@section('content')
<div class="router-detail-page">
<div class="topbar">
    <div>
        <h1>{{ $mikrotikRouter->name }}</h1>
        <div class="muted">{{ $mikrotikRouter->ip_address }}:{{ $mikrotikRouter->api_port }} · RouterOS API target</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('mikrotik-routers.edit', $mikrotikRouter) }}">Edit Router</a>
        <a class="btn light" href="{{ route('mikrotik-routers.index') }}">Back</a>
    </div>
</div>

@if ($inactiveProfileExists !== true)
    <div class="alert error">
        @if ($inactiveProfileExists === false)
            <strong>Inactive profile not found on this MikroTik.</strong>
            <div class="muted" style="margin-top:4px">Profile "{{ $mikrotikRouter->inactive_pppoe_profile }}" is missing on this router.</div>
        @else
            <strong>Could not verify inactive profile.</strong>
            <div class="muted" style="margin-top:4px">{{ $inactiveProfileError ?? 'Could not verify the inactive profile on this MikroTik right now.' }}</div>
        @endif
        <form method="post" action="{{ route('mikrotik-routers.inactive-profile.create', $mikrotikRouter) }}" onsubmit="return confirm('Create inactive profile on this router now?')">
            @csrf
            <button class="btn danger" style="margin-top:10px" type="submit">Create Inactive Profile</button>
        </form>
    </div>
@endif

<div class="card router-health-card" style="margin-bottom:16px; border:1px solid var(--line)">
    <div class="actions" style="justify-content:space-between; align-items:center">
        <div class="actions" style="gap:18px">
            <div><span class="muted">Router</span><br><strong>{{ $mikrotikRouter->ip_address }}:{{ $mikrotikRouter->api_port }}</strong></div>
            <div><span class="muted">API</span><br><span class="badge {{ $mikrotikRouter->last_api_status ?? 'checking' }}">{{ ucfirst($mikrotikRouter->last_api_status ?? 'Unknown') }}</span></div>
            <div><span class="muted">Ping</span><br><span class="badge {{ $mikrotikRouter->last_ping_status ?? 'checking' }}">{{ ucfirst($mikrotikRouter->last_ping_status ?? 'Unknown') }}</span></div>
            <div><span class="muted">App status</span><br><span class="badge {{ $mikrotikRouter->status }}">{{ ucfirst($mikrotikRouter->status) }}</span></div>
        </div>
        <div class="muted">Last checked: {{ $mikrotikRouter->last_checked_at?->format('Y-m-d H:i:s') ?? 'Never' }}</div>
    </div>
</div>

<div class="card router-import-card" style="margin-bottom:16px; border:1px solid var(--primary, var(--line))">
    <div class="actions" style="justify-content:space-between; align-items:flex-start; margin-bottom:14px">
        <div>
            <h2 style="margin-bottom:4px">Import from this MikroTik</h2>
            <div class="muted">প্রতিটি data এই router থেকেই আনা হবে। Profile না থাকলে local Package-এ auto যোগ হবে; billing শুরুর আগে package price ঠিক করুন।</div>
        </div>
        <div class="actions">
            <a class="btn light" href="{{ route('mikrotik-routers.profiles.index', $mikrotikRouter) }}">PPP Profiles</a>
            <a class="btn light" href="{{ route('mikrotik-routers.pools.index', $mikrotikRouter) }}">IP Pools</a>
            <a class="btn light" href="{{ route('mikrotik-routers.imported-secrets.index', $mikrotikRouter) }}">PPPoE Users</a>
            <a class="btn light" href="{{ route('mikrotik-routers.compare', $mikrotikRouter) }}">Compare & Sync</a>
        </div>
    </div>

    <div class="grid" style="gap:12px">
        <div class="router-import-tile" style="border:1px solid var(--line); border-radius:8px; padding:14px">
            <strong>1. PPP Profiles</strong>
            <div class="muted" style="min-height:42px; margin:6px 0 12px">Profile, speed/rate ও address settings আনুন। নতুন profile local package হবে।</div>
            <form method="post" action="{{ route('mikrotik-routers.import.profiles', $mikrotikRouter) }}">@csrf<button class="btn secondary" type="submit">Import PPP Profiles</button></form>
        </div>
        <div class="router-import-tile" style="border:1px solid var(--line); border-radius:8px; padding:14px">
            <strong>2. IP Pools</strong>
            <div class="muted" style="min-height:42px; margin:6px 0 12px">IP pool name, ranges ও next-pool তথ্য আলাদা তালিকায় আনুন।</div>
            <form method="post" action="{{ route('mikrotik-routers.import.ip-pools', $mikrotikRouter) }}">@csrf<button class="btn secondary" type="submit">Import IP Pools</button></form>
        </div>
        <div class="router-import-tile" style="border:1px solid var(--line); border-radius:8px; padding:14px">
            <strong>3. PPPoE Users / Secrets</strong>
            <div class="muted" style="min-height:42px; margin:6px 0 12px">Users আনুন, select করুন, তারপর Party ও Special ISP Customer হিসেবে তৈরি করুন।</div>
            <form method="post" action="{{ route('mikrotik-routers.import.secrets', $mikrotikRouter) }}">@csrf<button class="btn" type="submit">Import PPPoE Users</button></form>
        </div>
    </div>
</div>

<div class="grid router-detail-grid">
    <div class="card router-info-card">
        <h2>Connection & Sync</h2>
        <div class="form-grid">
            <div><span class="muted">API username</span><br><strong>{{ $mikrotikRouter->username }}</strong></div>
            <div><span class="muted">Password</span><br><strong>********</strong></div>
            <div><span class="muted">PPPoE sync interval</span><br><strong>Every {{ $mikrotikRouter->pppoe_sync_interval_minutes }} minute(s)</strong></div>
            <div><span class="muted">Inactive profile</span><br><strong>{{ $mikrotikRouter->inactive_pppoe_profile }}</strong></div>
            <div><span class="muted">Last PPPoE sync</span><br><strong>{{ $mikrotikRouter->last_pppoe_sync_at?->format('Y-m-d H:i:s') ?? 'Never' }}</strong></div>
            <div><span class="muted">Last online</span><br><strong>{{ $mikrotikRouter->last_online_at?->format('Y-m-d H:i:s') ?? 'Never' }}</strong></div>
        </div>
    </div>
    <div class="card router-info-card">
        <h2>Health & Notes</h2>
        <p><strong>API duration:</strong> {{ $mikrotikRouter->api_status_since ? $mikrotikRouter->api_status_since->diffForHumans(null, true) : 'Never checked' }}</p>
        <p><strong>Ping duration:</strong> {{ $mikrotikRouter->ping_status_since ? $mikrotikRouter->ping_status_since->diffForHumans(null, true) : 'Never checked' }}</p>
        <p><strong>Last connection:</strong> {{ $mikrotikRouter->last_connection_message ?? 'No check yet' }}</p>
        <p><strong>Last sync summary:</strong> {{ $mikrotikRouter->last_pppoe_sync_summary ?? 'No sync yet' }}</p>
        <p><strong>Notes:</strong> {{ $mikrotikRouter->notes ?? 'No notes' }}</p>
    </div>
</div>
</div>
@endsection
