@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add User</h1><div class="muted">Create a login user and assign access</div></div>
    <a class="btn light" href="{{ route('users.index') }}">Back</a>
</div>

<form method="post" action="{{ route('users.store') }}" class="card form-grid">
    @csrf
    <div><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
    <div><label>Password</label><input type="password" name="password" required></div>

    <div class="full">
        <h2>Roles</h2>
        <div class="grid two">
            @foreach ($roles as $role)
                <label><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', [])))> {{ $role->label }}</label>
            @endforeach
        </div>
    </div>

    <div class="full">
        <h2>Direct Permissions</h2>
        <div class="grid two">
            @foreach ($permissions as $permission)
                <label><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', [])))> {{ $permission->label }}</label>
            @endforeach
        </div>
    </div>

    <div class="full"><button class="btn" type="submit">Save User</button></div>
</form>
@endsection
