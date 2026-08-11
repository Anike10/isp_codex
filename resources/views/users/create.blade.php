@extends('layouts.app')

@section('content')
@php
    $selectedRoles = array_map('intval', old('roles', []));
    $selectedPermissions = array_map('intval', old('permissions', []));
    $selectedMenus = old('menus', []);
@endphp

<div class="topbar">
    <div>
        <h1>Add User</h1>
        <div class="muted">Create a login user and assign exact menu access</div>
    </div>
    <a class="btn light" href="{{ route('users.index') }}">Back</a>
</div>

<form method="post" action="{{ route('users.store') }}" class="access-layout">
    @csrf
    <input type="hidden" name="menu_access_present" value="1">

    <div class="card profile-panel">
        <h2>Login Details</h2>
        <div class="grid" style="gap:14px">
            <div>
                <label>Name</label>
                <input name="name" value="{{ old('name') }}" required>
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>Linked Reseller</label>
                <select name="reseller_id">
                    <option value="">Not a reseller login</option>
                    @foreach ($resellers as $reseller)
                        <option value="{{ $reseller->id }}" @selected((int) old('reseller_id', request('reseller_id')) === $reseller->id)>{{ $reseller->name }} &mdash; {{ $reseller->phone }}</option>
                    @endforeach
                </select>
                <span class="muted">Select this for users with the Reseller role.</span>
            </div>
            <button class="btn user-submit-primary" type="submit">Save User</button>
        </div>
    </div>

    @include('users.partials.access-control', ['submitLabel' => 'Save User'])
</form>
@endsection
