@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $mikrotikRouter->name }}</h1>
        <div class="muted">{{ $mikrotikRouter->ip_address }}:{{ $mikrotikRouter->api_port }}</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('mikrotik-routers.edit', $mikrotikRouter) }}">Edit</a>
        <a class="btn light" href="{{ route('mikrotik-routers.index') }}">Back</a>
    </div>
</div>

<div class="card">
    <p><strong>IP Address:</strong> {{ $mikrotikRouter->ip_address }}</p>
    <p><strong>API Port:</strong> {{ $mikrotikRouter->api_port }}</p>
    <p><strong>PPPoE Sync Interval:</strong> Every {{ $mikrotikRouter->pppoe_sync_interval_minutes }} minute(s)</p>
    <p><strong>Inactive PPPoE Profile:</strong> {{ $mikrotikRouter->inactive_pppoe_profile }}</p>
    <p><strong>Last PPPoE Sync:</strong> {{ $mikrotikRouter->last_pppoe_sync_at?->format('Y-m-d H:i:s') ?? 'Never' }}</p>
    <p><strong>Last PPPoE Sync Summary:</strong> {{ $mikrotikRouter->last_pppoe_sync_summary ?? 'No sync yet' }}</p>
    <p><strong>Username:</strong> {{ $mikrotikRouter->username }}</p>
    <p><strong>Password:</strong> ********</p>
    <p><strong>Status:</strong> <span class="badge {{ $mikrotikRouter->status }}">{{ $mikrotikRouter->status }}</span></p>
    <p><strong>API Status:</strong> <span class="badge {{ $mikrotikRouter->last_api_status ?? 'checking' }}">{{ ucfirst($mikrotikRouter->last_api_status ?? 'unknown') }}</span></p>
    <p><strong>API Status Duration:</strong> {{ $mikrotikRouter->api_status_since ? $mikrotikRouter->api_status_since->diffForHumans(null, true) : 'Never checked' }}</p>
    <p><strong>Ping Status:</strong> <span class="badge {{ $mikrotikRouter->last_ping_status ?? 'checking' }}">{{ ucfirst($mikrotikRouter->last_ping_status ?? 'unknown') }}</span></p>
    <p><strong>Ping Status Duration:</strong> {{ $mikrotikRouter->ping_status_since ? $mikrotikRouter->ping_status_since->diffForHumans(null, true) : 'Never pinged' }}</p>
    <p><strong>Last Checked:</strong> {{ $mikrotikRouter->last_checked_at?->format('Y-m-d H:i:s') ?? 'Never' }}</p>
    <p><strong>Last Online:</strong> {{ $mikrotikRouter->last_online_at?->format('Y-m-d H:i:s') ?? 'Never' }}</p>
    <p><strong>Last Offline:</strong> {{ $mikrotikRouter->last_offline_at?->format('Y-m-d H:i:s') ?? 'Never' }}</p>
    <p><strong>Last Ping:</strong> {{ $mikrotikRouter->last_ping_at?->format('Y-m-d H:i:s') ?? 'Never' }}</p>
    <p><strong>Last Connection Message:</strong> {{ $mikrotikRouter->last_connection_message ?? 'No check yet' }}</p>
    <p><strong>Notes:</strong> {{ $mikrotikRouter->notes ?? 'No notes' }}</p>
</div>
@endsection
