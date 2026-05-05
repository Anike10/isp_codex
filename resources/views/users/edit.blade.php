@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Edit User</h1><div class="muted">{{ $user->email }}</div></div>
    <a class="btn light" href="{{ route('users.index') }}">Back</a>
</div>

<form method="post" action="{{ route('users.update', $user) }}" class="card form-grid">
    @csrf
    @method('PUT')
    <div><label>Name</label><input name="name" value="{{ old('name', $user->name) }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
    <div><label>New Password</label><input type="password" name="password"><span class="muted">Leave blank to keep current password.</span></div>

    <div class="full">
        <h2>Roles</h2>
        <div class="grid two">
            @foreach ($roles as $role)
                <label><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', $user->roles->pluck('id')->all())))> {{ $role->label }}</label>
            @endforeach
        </div>
    </div>

    <div class="full">
        <h2>Direct Permissions</h2>
        <div class="grid two">
            @foreach ($permissions as $permission)
                <label><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', $user->permissions->pluck('id')->all())))> {{ $permission->label }}</label>
            @endforeach
        </div>
    </div>

    <div class="full"><button class="btn" type="submit">Update User</button></div>
</form>
@endsection
