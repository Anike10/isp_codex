@extends('layouts.app')

@section('content')
<div class="router-subpage">
<div class="topbar">
    <div>
        <h1>Imported PPPoE Secrets: {{ $mikrotikRouter->name }}</h1>
        <div class="muted">Source MikroTik: {{ $mikrotikRouter->ip_address }}:{{ $mikrotikRouter->api_port }}</div>
    </div>
    <div class="actions">
        <form method="post" action="{{ route('mikrotik-routers.import.secrets', $mikrotikRouter) }}">
            @csrf
            <button class="btn secondary" type="submit">Refresh Secrets</button>
        </form>
        <form method="post" action="{{ route('mikrotik-routers.import.active-users', $mikrotikRouter) }}" style="display:flex; gap:6px">
            @csrf
            <input type="text" name="active_password" required placeholder="Shared password" autocomplete="off" style="width:150px">
            <button class="btn secondary" type="submit">Import Active Users</button>
        </form>
        <a class="btn light" href="{{ route('mikrotik-routers.show', $mikrotikRouter) }}">Back to Router</a>
    </div>
</div>
<div class="muted" style="margin:-4px 0 12px">“Import Active Users” pulls every user in <code>/ppp/active</code>. Anyone without a real <code>/ppp/secret</code> is stored with the shared password so you can create them as parties below; real secrets keep their own password.</div>

<form method="get" class="actions per-page-form">
    <label class="per-page-label">Rows per page <input class="per-page-select" type="number" name="per_page" min="1" max="500" value="{{ $perPage }}"></label>
    <button class="btn light" type="submit">Apply</button>
    <button class="btn light" type="submit" name="make_per_page_default" value="1">ডিফল্ট সেট করুন</button>
</form>

<form id="bulk-party-form" method="post" action="{{ route('mikrotik-routers.imported-secrets.create-parties', $mikrotikRouter) }}">
    @csrf
    <div class="card" style="margin-bottom:16px">
        <div class="actions" style="justify-content:space-between">
            <div>
                <strong>Create selected as Party</strong>
                <div class="muted">Connection ID, PPPoE password, profile, MikroTik target ও source note নিজে থেকেই বসবে।</div>
            </div>
            <div class="actions">
                <label style="font-weight:400; display:flex; gap:6px; align-items:center"><input type="checkbox" name="never_suspend" value="1" style="width:auto"> Special ISP Customer</label>
                <label style="font-weight:400; display:flex; gap:6px; align-items:center"><input type="checkbox" name="update_existing" value="1" style="width:auto"> Update existing party</label>
                <button class="btn" type="submit">Create Selected Parties</button>
            </div>
        </div>
    </div>
</form>

    <table>
        <thead>
            <tr>
                <th><input type="checkbox" data-select-all style="width:auto" aria-label="Select all secrets"></th>
                <th>Connection ID</th>
                <th>Profile / Service</th>
                <th>Address</th>
                <th>Device MAC</th>
                <th>Status</th>
                <th>Router source / note</th>
                <th>Party</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($secrets as $secret)
                <tr>
                    <td><input type="checkbox" name="secret_ids[]" value="{{ $secret->id }}" form="bulk-party-form" style="width:auto"></td>
                    <td><strong>{{ $secret->name }}</strong><div class="muted">Password imported securely</div></td>
                    <td>{{ $secret->profile ?: 'No profile' }}<div class="muted">{{ $secret->service ?: 'Unknown service' }}</div></td>
                    <td>{{ $secret->remote_address ?: '—' }}<div class="muted">{{ $secret->local_address ?: '' }}</div></td>
                    <td>@if ($secret->device_mac)<code>{{ $secret->device_mac }}</code>@else<span class="muted">—</span>@endif</td>
                    <td>
                        @if ($mikrotikRouter->read_only)
                            <span class="badge">Read-only</span>
                        @else
                            <span class="badge {{ $secret->disabled ? 'inactive' : 'active' }}">{{ $secret->disabled ? 'Disabled' : 'Enabled' }}</span>
                        @endif
                    </td>
                    <td>{{ $mikrotikRouter->name }}<div class="muted">{{ $secret->router_comment ?: 'No router comment' }}</div></td>
                    <td>
                        @if ($secret->customer)
                            <a href="{{ route('customers.show', $secret->customer) }}">{{ $secret->customer->name }}</a>
                        @else
                            <span class="muted">Not created</span>
                        @endif
                    </td>
                    <td>
                        <details>
                            <summary>Edit note</summary>
                            <form method="post" action="{{ route('mikrotik-routers.imported-secrets.update', [$mikrotikRouter, $secret]) }}" style="margin-top:8px; min-width:260px">
                                @csrf
                                @method('PATCH')
                                <label>Profile</label><input name="profile" value="{{ $secret->profile }}">
                                <label>Router comment</label><input name="router_comment" value="{{ $secret->router_comment }}">
                                <label>Local note</label><textarea name="notes">{{ $secret->notes }}</textarea>
                                <button class="btn light" type="submit">Save note</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">No PPPoE secrets imported yet. Use Import PPPoE Users / Secrets from the router page.</td></tr>
            @endforelse
        </tbody>
    </table>
<div style="margin-top:16px">{{ $secrets->links() }}</div>

<div class="grid" style="margin-top:16px">
    <div class="card">
        <h2>Imported PPP Profiles</h2>
        <form method="get" class="actions per-page-form">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <label class="per-page-label">Profiles per page <input class="per-page-select" type="number" name="profile_per_page" min="1" max="500" value="{{ $profilePerPage }}"></label>
            <button class="btn light" type="submit">Apply</button>
            <button class="btn light" type="submit" name="make_profile_per_page_default" value="1">ডিফল্ট সেট করুন</button>
        </form>
        <table>
            <thead><tr><th>#</th><th>Profile</th><th>Rate limit</th><th>Local address</th><th>Remote pool/address</th><th>Status</th></tr></thead>
            <tbody>@forelse ($profiles as $profile)
                <tr><td>{{ $profiles->firstItem() + $loop->index }}</td><td><strong>{{ $profile->name }}</strong></td><td>{{ $profile->rate_limit ?: '—' }}</td><td>{{ $profile->local_address ?: '—' }}</td><td>{{ $profile->remote_address ?: '—' }}</td><td>@if ($mikrotikRouter->read_only)<span class="badge">Read-only</span>@else<span class="badge {{ $profile->disabled ? 'inactive' : 'active' }}">{{ $profile->disabled ? 'Disabled' : 'Enabled' }}</span>@endif</td></tr>
            @empty<tr><td colspan="6">No profiles imported yet.</td></tr>@endforelse</tbody>
        </table>
        <div style="margin-top:12px">{{ $profiles->links() }}</div>
    </div>
    <div class="card">
        <h2>Imported IP Pools</h2>
        @forelse ($pools as $pool)
            <div style="border-bottom:1px solid var(--line); padding:7px 0"><strong>{{ $pool->name }}</strong><div class="muted">{{ $pool->ranges ?: 'No ranges' }}</div></div>
        @empty
            <div class="muted">No IP pools imported yet.</div>
        @endforelse
    </div>
</div>

<script>
document.querySelector('[data-select-all]')?.addEventListener('change', function () {
    document.querySelectorAll('input[name="secret_ids[]"]').forEach(input => input.checked = this.checked);
});
</script>
</div>
@endsection
