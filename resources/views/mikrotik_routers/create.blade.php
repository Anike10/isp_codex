@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Add MikroTik Router</h1>
        <div class="muted">Save RouterOS API login details for later automation</div>
    </div>
    <a class="btn light" href="{{ route('mikrotik-routers.index') }}">Back</a>
</div>

<form method="post" action="{{ route('mikrotik-routers.store') }}" class="card form-grid" autocomplete="off">
    @csrf
    <div aria-hidden="true" style="position:fixed;left:-10000px;top:-10000px;width:1px;height:1px;overflow:hidden">
        <input name="browser_login_hint" autocomplete="username" tabindex="-1">
        <input type="password" name="browser_password_hint" autocomplete="current-password" tabindex="-1">
    </div>
    <div>
        <label>Router Name</label>
        <input name="name" value="{{ old('name', 'Main MikroTik') }}" required>
    </div>
    <div>
        <label>IP Address</label>
        <input name="ip_address" value="{{ old('ip_address', '192.168.6.1') }}" required>
    </div>
    <div>
        <label>API Port</label>
        <input type="number" min="1" max="65535" name="api_port" value="{{ old('api_port', 8728) }}" required>
    </div>
    <div>
        <label>PPPoE Sync Interval Minutes</label>
        <input type="number" min="60" max="1440" step="60" name="pppoe_sync_interval_minutes" value="{{ old('pppoe_sync_interval_minutes', 60) }}" required>
        <span class="muted">Default 60 minutes. The hourly scheduler checks whether this router is due.</span>
    </div>
    <div>
        <label>Inactive PPPoE Profile</label>
        <input name="inactive_pppoe_profile" value="{{ old('inactive_pppoe_profile', 'inactive') }}" required>
        <span class="muted">Inactive users will be moved to this profile, not disabled.</span>
    </div>
    <div>
        <label>Username</label>
        <input name="router_api_username" value="{{ old('router_api_username', old('username', 'admin')) }}" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" required>
    </div>
    <div>
        <label>Password</label>
        <input type="password" name="router_api_password" autocomplete="new-password" required>
    </div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="full">
        <label style="display:flex;gap:8px;align-items:center;font-weight:400">
            <input type="checkbox" name="read_only" value="1" style="width:auto" @checked(old('read_only'))>
            API user is read-only (import only)
        </label>
        <span class="muted">Tick when the RouterOS API login can only read. Importing every secret and profile still works; the app will not push changes to this router, and its users/profiles show as &ldquo;Read-only&rdquo;.</span>
    </div>
    <div class="full">
        <label>Notes</label>
        <textarea name="notes">{{ old('notes') }}</textarea>
    </div>
    <div class="full">
        <button class="btn" type="submit">Save Router</button>
    </div>
</form>
@endsection
