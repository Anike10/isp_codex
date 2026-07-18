@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Edit MikroTik Router</h1>
        <div class="muted">Update RouterOS API IP, username, password, and port</div>
    </div>
    <a class="btn light" href="{{ route('mikrotik-routers.show', $mikrotikRouter) }}">Back</a>
</div>

<form method="post" action="{{ route('mikrotik-routers.update', $mikrotikRouter) }}" class="card form-grid" id="router-edit-form" autocomplete="off">
    @csrf
    @method('PUT')
    <div aria-hidden="true" style="position:fixed;left:-10000px;top:-10000px;width:1px;height:1px;overflow:hidden">
        <input name="browser_login_hint" autocomplete="username" tabindex="-1">
        <input type="password" name="browser_password_hint" autocomplete="current-password" tabindex="-1">
    </div>

    <div>
        <label>Router Name</label>
        <input name="name" value="{{ old('name', $mikrotikRouter->name) }}" required>
    </div>
    <div>
        <label>IP Address</label>
        <input name="ip_address" value="{{ old('ip_address', $mikrotikRouter->ip_address) }}" required>
    </div>
    <div>
        <label>API Port</label>
        <input type="number" min="1" max="65535" name="api_port" value="{{ old('api_port', $mikrotikRouter->api_port) }}" required>
    </div>
    <div>
        <label>PPPoE Sync Interval Minutes</label>
        <input type="number" min="60" max="1440" step="60" name="pppoe_sync_interval_minutes" value="{{ old('pppoe_sync_interval_minutes', $mikrotikRouter->pppoe_sync_interval_minutes) }}" required>
        <span class="muted">Default 60 minutes. The hourly scheduler checks whether this router is due.</span>
    </div>
    <div>
        <label>Inactive PPPoE Profile</label>
        <input name="inactive_pppoe_profile" value="{{ old('inactive_pppoe_profile', $mikrotikRouter->inactive_pppoe_profile) }}" required>
        <span class="muted">Inactive users will be moved to this profile, not disabled.</span>
    </div>
    <div>
        <label>Username</label>
        <div class="actions" style="gap:6px">
            <input id="router-username" name="router_api_username" value="{{ old('router_api_username', old('username', $mikrotikRouter->username)) }}" data-saved-username="{{ $mikrotikRouter->username }}" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" readonly required>
            <button class="btn light" type="button" id="unlock-router-username">Change Username</button>
        </div>
        <span class="muted">Locked by default so browser autofill cannot change it accidentally.</span>
    </div>
    <div>
        <label>New Password</label>
        <div class="actions" style="gap:6px">
            <input id="router-password" type="password" name="router_api_password" autocomplete="new-password" readonly>
            <button class="btn light" type="button" id="unlock-router-password">Change Password</button>
        </div>
        <span class="muted">Locked and blank by default. Unlock only when the RouterOS API password must change.</span>
    </div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', $mikrotikRouter->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $mikrotikRouter->status) === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="full">
        <label>Notes</label>
        <textarea name="notes">{{ old('notes', $mikrotikRouter->notes) }}</textarea>
    </div>
    <div class="full">
        <button class="btn" type="submit">Update Router</button>
    </div>
</form>
<script>
const routerEditForm = document.getElementById('router-edit-form');
const routerUsername = document.getElementById('router-username');
const routerPassword = document.getElementById('router-password');
let routerUsernameUnlocked = false;
let routerPasswordUnlocked = false;

document.getElementById('unlock-router-username')?.addEventListener('click', function () {
    routerUsernameUnlocked = true;
    routerUsername.readOnly = false;
    routerUsername.focus();
    routerUsername.select();
    this.textContent = 'Username Editable';
});

document.getElementById('unlock-router-password')?.addEventListener('click', function () {
    routerPasswordUnlocked = true;
    routerPassword.readOnly = false;
    routerPassword.value = '';
    routerPassword.focus();
    this.textContent = 'Password Editable';
});

function restoreLockedRouterCredentials() {
    if (!routerUsernameUnlocked) routerUsername.value = routerUsername.dataset.savedUsername;
    if (!routerPasswordUnlocked) routerPassword.value = '';
}

window.addEventListener('pageshow', restoreLockedRouterCredentials);
[100, 500, 1500].forEach(delay => setTimeout(restoreLockedRouterCredentials, delay));
routerEditForm.addEventListener('submit', restoreLockedRouterCredentials);
</script>
@endsection
