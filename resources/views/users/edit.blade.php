@extends('layouts.app')

@section('content')
@php
    $selectedRoles = old('roles', $user->roles->pluck('id')->all());
    $selectedPermissions = old('permissions', $user->permissions->pluck('id')->all());
@endphp

<style>
    .access-layout { display:grid; grid-template-columns:minmax(260px, 360px) minmax(0, 1fr); gap:16px; align-items:start; }
    .profile-panel { position:sticky; top:84px; }
    .section-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:12px; }
    .section-head h2 { margin-bottom:4px; }
    .mini-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .mini-btn { border:1px solid var(--line); background:#f8fafc; color:var(--ink); border-radius:6px; padding:7px 10px; cursor:pointer; font-weight:700; font-size:12px; }
    .mini-btn:hover { background:#eef4fb; }
    .choice-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px; }
    .choice-card { border:1px solid var(--line); border-radius:8px; padding:12px; display:flex; gap:10px; align-items:flex-start; background:#fff; cursor:pointer; min-height:58px; }
    .choice-card:hover { border-color:#9db4cf; background:#f8fbff; }
    .choice-card input { width:auto; margin-top:3px; flex:0 0 auto; }
    .choice-title { display:block; font-weight:700; line-height:1.25; }
    .choice-sub { display:block; margin-top:3px; color:var(--muted); font-size:12px; line-height:1.35; word-break:break-word; }
    .access-search { margin-bottom:12px; }
    .access-count { color:var(--muted); font-size:13px; }
    @media (max-width: 980px) {
        .access-layout { grid-template-columns:1fr; }
        .profile-panel { position:static; }
    }
</style>

<div class="topbar">
    <div>
        <h1>Edit User</h1>
        <div class="muted">{{ $user->email }} | Last updated {{ $user->updated_at?->format('Y-m-d H:i') }}</div>
    </div>
    <a class="btn light" href="{{ route('users.index') }}">Back</a>
</div>

<form method="post" action="{{ route('users.update', $user) }}" class="access-layout">
    @csrf
    @method('PUT')

    <div class="card profile-panel">
        <h2>Login Details</h2>
        <div class="grid" style="gap:14px">
            <div>
                <label>Name</label>
                <input name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div>
                <label>New Password</label>
                <input type="password" name="password">
                <span class="muted">Leave blank to keep current password.</span>
            </div>
            <div>
                <label>Linked Reseller</label>
                <select name="reseller_id">
                    <option value="">Not a reseller login</option>
                    @foreach ($resellers as $reseller)
                        <option value="{{ $reseller->id }}" @selected((int) old('reseller_id', $user->reseller_id) === $reseller->id)>{{ $reseller->name }} — {{ $reseller->phone }}</option>
                    @endforeach
                </select>
                <span class="muted">Select this for users with the Reseller role.</span>
            </div>
            <button class="btn" type="submit">Update User</button>
        </div>
    </div>

    <div class="grid">
        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Roles</h2>
                    <div class="access-count"><span data-count-for="role-check">0</span> selected</div>
                </div>
                <div class="mini-actions">
                    <button class="mini-btn" type="button" data-select-all="role-check">Select All</button>
                    <button class="mini-btn" type="button" data-clear-all="role-check">Clear</button>
                </div>
            </div>
            <div class="choice-grid">
                @foreach ($roles as $role)
                    <label class="choice-card">
                        <input class="role-check" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $selectedRoles))>
                        <span>
                            <span class="choice-title">{{ $role->label }}</span>
                            <span class="choice-sub">{{ $role->name }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Direct Permissions</h2>
                    <div class="access-count"><span data-count-for="permission-check">0</span> selected</div>
                </div>
                <div class="mini-actions">
                    <button class="mini-btn" type="button" data-select-all="permission-check">Select All</button>
                    <button class="mini-btn" type="button" data-clear-all="permission-check">Clear</button>
                </div>
            </div>
            <input class="access-search" id="permissionSearch" placeholder="Search permissions">
            <div class="choice-grid" id="permissionGrid">
                @foreach ($permissions as $permission)
                    <label class="choice-card" data-permission-text="{{ strtolower($permission->label.' '.$permission->name) }}">
                        <input class="permission-check" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selectedPermissions))>
                        <span>
                            <span class="choice-title">{{ $permission->label }}</span>
                            <span class="choice-sub">{{ $permission->name }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </section>
    </div>
</form>

<script>
function updateCount(className) {
    const count = document.querySelectorAll(`.${className}:checked`).length;
    document.querySelectorAll(`[data-count-for="${className}"]`).forEach(item => item.textContent = count);
}

document.querySelectorAll('[data-select-all]').forEach(button => {
    button.addEventListener('click', () => {
        const className = button.dataset.selectAll;
        document.querySelectorAll(`.${className}`).forEach(input => input.checked = true);
        updateCount(className);
    });
});

document.querySelectorAll('[data-clear-all]').forEach(button => {
    button.addEventListener('click', () => {
        const className = button.dataset.clearAll;
        document.querySelectorAll(`.${className}`).forEach(input => input.checked = false);
        updateCount(className);
    });
});

document.querySelectorAll('.role-check, .permission-check').forEach(input => {
    input.addEventListener('change', () => updateCount(input.classList.contains('role-check') ? 'role-check' : 'permission-check'));
});

document.getElementById('permissionSearch')?.addEventListener('input', event => {
    const query = event.target.value.trim().toLowerCase();
    document.querySelectorAll('#permissionGrid .choice-card').forEach(card => {
        card.style.display = card.dataset.permissionText.includes(query) ? 'flex' : 'none';
    });
});

updateCount('role-check');
updateCount('permission-check');
</script>
@endsection
