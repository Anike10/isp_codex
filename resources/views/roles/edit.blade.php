@extends('layouts.app')

@section('content')
@php
    $selectedPermissions = old('permissions', $role->permissions->pluck('id')->all());
@endphp

<style>
    .role-layout { display:grid; grid-template-columns:minmax(260px, 360px) minmax(0, 1fr); gap:16px; align-items:start; }
    .role-panel { position:sticky; top:84px; }
    .permission-toolbar { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:12px; }
    .mini-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .mini-btn { border:1px solid var(--line); background:#f8fafc; color:var(--ink); border-radius:6px; padding:7px 10px; cursor:pointer; font-weight:700; font-size:12px; }
    .mini-btn:hover { background:#eef4fb; }
    .permission-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:10px; }
    .permission-card { border:1px solid var(--line); border-radius:8px; padding:12px; display:flex; gap:10px; align-items:flex-start; min-height:62px; cursor:pointer; background:white; }
    .permission-card:hover { border-color:#9db4cf; background:#f8fbff; }
    .permission-card input { width:auto; margin-top:3px; flex:0 0 auto; }
    .permission-title { display:block; font-weight:700; line-height:1.25; }
    .permission-key { display:block; color:var(--muted); font-size:12px; margin-top:4px; word-break:break-word; }
    .permission-search { margin-bottom:12px; }
    .selected-count { color:var(--muted); font-size:13px; }
    .role-meta { display:grid; gap:8px; margin-top:14px; padding-top:14px; border-top:1px solid #edf1f5; }
    .role-meta div { display:flex; justify-content:space-between; gap:10px; color:var(--muted); }
    .role-meta strong { color:var(--ink); }
    @media (max-width: 980px) {
        .role-layout { grid-template-columns:1fr; }
        .role-panel { position:static; }
    }
</style>

<div class="topbar">
    <div>
        <h1>Edit Role</h1>
        <div class="muted">{{ $role->label }} | {{ $role->name }}</div>
    </div>
    <a class="btn light" href="{{ route('roles.index') }}">Back</a>
</div>

<form method="post" action="{{ route('roles.update', $role) }}" class="role-layout">
    @csrf
    @method('PUT')

    <div class="card role-panel">
        <h2>Role Details</h2>
        <div class="grid" style="gap:14px">
            <div>
                <label>Role Key</label>
                <input name="name" value="{{ old('name', $role->name) }}" required>
                <span class="muted">Use lowercase letters, numbers, dash, or underscore.</span>
            </div>
            <div>
                <label>Role Label</label>
                <input name="label" value="{{ old('label', $role->label) }}" required>
            </div>
            <button class="btn" type="submit">Update Role</button>
        </div>
        <div class="role-meta">
            <div><span>Current Permissions</span><strong>{{ count($selectedPermissions) }}</strong></div>
            <div><span>Created</span><strong>{{ $role->created_at?->format('Y-m-d') ?? 'N/A' }}</strong></div>
            <div><span>Updated</span><strong>{{ $role->updated_at?->format('Y-m-d') ?? 'N/A' }}</strong></div>
        </div>
    </div>

    <section class="card">
        <div class="permission-toolbar">
            <div>
                <h2>Permissions</h2>
                <div class="selected-count"><span id="permissionCount">0</span> selected</div>
            </div>
            <div class="mini-actions">
                <button class="mini-btn" type="button" id="selectAllPermissions">Select All</button>
                <button class="mini-btn" type="button" id="clearPermissions">Clear</button>
            </div>
        </div>
        <input class="permission-search" id="permissionSearch" placeholder="Search permissions">
        <div class="permission-grid" id="permissionGrid">
            @foreach ($permissions as $permission)
                <label class="permission-card" data-permission-text="{{ strtolower($permission->label.' '.$permission->name) }}">
                    <input class="permission-check" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selectedPermissions))>
                    <span>
                        <span class="permission-title">{{ $permission->label }}</span>
                        <span class="permission-key">{{ $permission->name }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </section>
</form>

<script>
const permissionChecks = () => Array.from(document.querySelectorAll('.permission-check'));
const permissionCount = document.getElementById('permissionCount');

function refreshPermissionCount() {
    permissionCount.textContent = permissionChecks().filter(input => input.checked).length;
}

document.getElementById('selectAllPermissions').addEventListener('click', () => {
    permissionChecks().forEach(input => input.checked = true);
    refreshPermissionCount();
});

document.getElementById('clearPermissions').addEventListener('click', () => {
    permissionChecks().forEach(input => input.checked = false);
    refreshPermissionCount();
});

document.querySelectorAll('.permission-check').forEach(input => input.addEventListener('change', refreshPermissionCount));

document.getElementById('permissionSearch').addEventListener('input', event => {
    const query = event.target.value.trim().toLowerCase();
    document.querySelectorAll('#permissionGrid .permission-card').forEach(card => {
        card.style.display = card.dataset.permissionText.includes(query) ? 'flex' : 'none';
    });
});

refreshPermissionCount();
</script>
@endsection
