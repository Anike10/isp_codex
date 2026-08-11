@extends('layouts.app')

@section('content')
@php
    $effectivePermissionIds = $user->permissions->pluck('id')
        ->merge($user->roles->flatMap(fn ($role) => $role->permissions->pluck('id')))
        ->diff($user->deniedPermissions->pluck('id'))
        ->unique()
        ->values()
        ->all();
    $selectedRoles = array_map('intval', old('roles', $user->roles->pluck('id')->all()));
    $selectedPermissions = array_map('intval', old('permissions', $effectivePermissionIds));
    $menuDefinitions = collect($menuGroups)->pluck('items')->collapse();
    $defaultSelectedMenus = $user->menuAccesses->isNotEmpty()
        ? $user->menuAccesses->where('allowed', true)->pluck('menu_key')->all()
        : $menuDefinitions->filter(fn ($item, $key) => $user->canAccessMenu($key))->keys()->all();
    $selectedMenus = old('menus', $defaultSelectedMenus);
@endphp

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
    <input type="hidden" name="menu_access_present" value="1">

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
                        <option value="{{ $reseller->id }}" @selected((int) old('reseller_id', $user->reseller_id) === $reseller->id)>{{ $reseller->name }} &mdash; {{ $reseller->phone }}</option>
                    @endforeach
                </select>
                <span class="muted">Select this for users with the Reseller role.</span>
            </div>
            <button class="btn user-submit-primary" type="submit">Update User</button>
        </div>
    </div>

    @include('users.partials.access-control', ['submitLabel' => 'Update User'])
</form>
@endsection
