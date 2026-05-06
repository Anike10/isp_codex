@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>MikroTik Routers</h1>
        <div class="muted">RouterOS API connection details</div>
    </div>
    <a class="btn" href="{{ route('mikrotik-routers.create') }}">Add Router</a>
</div>

@include('partials.per_page')

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>IP Address</th>
            <th>API Port</th>
            <th>Username</th>
            <th>Status</th>
            <th>API</th>
            <th>Ping</th>
            <th>PPPoE Sync</th>
            <th>Last Online</th>
            <th>Last Offline</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($routers as $router)
            <tr data-href="{{ route('mikrotik-routers.show', $router) }}">
                <td>{{ $router->name }}</td>
                <td>{{ $router->ip_address }}</td>
                <td>{{ $router->api_port }}</td>
                <td>{{ $router->username }}</td>
                <td><span class="badge {{ $router->status }}">{{ $router->status }}</span></td>
                <td class="connection-cell">
                    <span
                        class="badge checking router-connection"
                        data-status-url="{{ route('mikrotik-routers.connection-status', $router) }}"
                        title="Waiting for first check"
                    >{{ ucfirst($router->last_api_status ?? 'Checking') }} {{ $router->api_status_since ? $router->api_status_since->diffForHumans(null, true) : '' }}</span>
                </td>
                <td class="connection-cell">
                    <span
                        class="badge checking router-ping"
                        title="Waiting for first ping"
                    >{{ ucfirst($router->last_ping_status ?? 'Checking') }} {{ $router->ping_status_since ? $router->ping_status_since->diffForHumans(null, true) : '' }}</span>
                </td>
                <td>
                    <div>Every {{ $router->pppoe_sync_interval_minutes }}m</div>
                    <div class="muted">{{ $router->last_pppoe_sync_at?->format('Y-m-d H:i:s') ?? 'Never' }}</div>
                </td>
                <td class="router-last-online">{{ $router->last_online_at?->format('Y-m-d H:i:s') ?? 'Never' }}</td>
                <td class="router-last-offline">{{ $router->last_offline_at?->format('Y-m-d H:i:s') ?? 'Never' }}</td>
                <td>
                    <div class="actions">
                        <a class="btn light" href="{{ route('mikrotik-routers.show', $router) }}">View</a>
                        <a class="btn secondary" href="{{ route('mikrotik-routers.edit', $router) }}">Edit</a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11">No MikroTik routers added yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $routers->links() }}</div>

<script>
const routerBadges = Array.from(document.querySelectorAll('.router-connection'));

function setStatusBadge(badge, statusClass, label, message) {
    badge.classList.remove('checking', 'online', 'offline', 'inactive');
    badge.classList.add(statusClass);
    badge.textContent = label;
    badge.title = message || label;
}

function setRouterStatus(badge, data) {
    const apiStatusClass = data.api_label === 'Inactive' ? 'inactive' : (data.api_online ? 'online' : 'offline');
    const apiLabel = data.api_duration ? `${data.api_label} ${data.api_duration}` : data.api_label;
    setStatusBadge(badge, apiStatusClass, apiLabel, data.message);

    const row = badge.closest('tr');
    const pingBadge = row.querySelector('.router-ping');
    const pingStatusClass = data.ping_label === 'Inactive' ? 'inactive' : (data.ping_online ? 'online' : 'offline');
    const pingLabel = data.ping_duration ? `${data.ping_label} ${data.ping_duration}` : data.ping_label;
    setStatusBadge(pingBadge, pingStatusClass, pingLabel, data.ping_message);

    const lastOnlineNode = row.querySelector('.router-last-online');
    if (lastOnlineNode) {
        lastOnlineNode.textContent = data.last_online_at || 'Never';
    }

    const lastOfflineNode = row.querySelector('.router-last-offline');
    if (lastOfflineNode) {
        lastOfflineNode.textContent = data.last_offline_at || 'Never';
    }

}

async function refreshRouterStatus(badge) {
    setStatusBadge(badge, 'checking', 'Checking', 'Checking MikroTik API connection...');

    try {
        const response = await fetch(badge.dataset.statusUrl, {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store',
        });
        const data = await response.json();
        setRouterStatus(badge, data);
    } catch (error) {
        setStatusBadge(badge, 'offline', 'Offline', error.message);
    }
}

function refreshAllRouterStatuses() {
    routerBadges.forEach(refreshRouterStatus);
}

if (routerBadges.length > 0) {
    refreshAllRouterStatuses();
    setInterval(refreshAllRouterStatuses, 10000);
}
</script>
@endsection
