@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Edit MikroTik Router</h1>
        <div class="muted">Update RouterOS API IP, username, password, and port</div>
    </div>
    <a class="btn light" href="{{ route('mikrotik-routers.show', $mikrotikRouter) }}">Back</a>
</div>

<form method="post" action="{{ route('mikrotik-routers.update', $mikrotikRouter) }}" class="card form-grid">
    @csrf
    @method('PUT')

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
        <label>Username</label>
        <input name="username" value="{{ old('username', $mikrotikRouter->username) }}" required>
    </div>
    <div>
        <label>New Password</label>
        <input type="password" name="password">
        <span class="muted">Leave blank to keep current password.</span>
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
@endsection
