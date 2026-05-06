@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Add MikroTik Router</h1>
        <div class="muted">Save RouterOS API login details for later automation</div>
    </div>
    <a class="btn light" href="{{ route('mikrotik-routers.index') }}">Back</a>
</div>

<form method="post" action="{{ route('mikrotik-routers.store') }}" class="card form-grid">
    @csrf
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
        <input type="number" min="1" max="1440" name="pppoe_sync_interval_minutes" value="{{ old('pppoe_sync_interval_minutes', 10) }}" required>
        <span class="muted">How often this router should verify all PPPoE users.</span>
    </div>
    <div>
        <label>Inactive PPPoE Profile</label>
        <input name="inactive_pppoe_profile" value="{{ old('inactive_pppoe_profile', 'inactive') }}" required>
        <span class="muted">Inactive users will be moved to this profile, not disabled.</span>
    </div>
    <div>
        <label>Username</label>
        <input name="username" value="{{ old('username', 'admin') }}" required>
    </div>
    <div>
        <label>Password</label>
        <input type="password" name="password" value="{{ old('password') }}" required>
    </div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
        </select>
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
