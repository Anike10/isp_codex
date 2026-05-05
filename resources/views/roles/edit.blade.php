@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Edit Role</h1><div class="muted">{{ $role->label }}</div></div>
    <a class="btn light" href="{{ route('roles.index') }}">Back</a>
</div>

<form method="post" action="{{ route('roles.update', $role) }}" class="card form-grid">
    @csrf
    @method('PUT')
    <div><label>Role Key</label><input name="name" value="{{ old('name', $role->name) }}" required></div>
    <div><label>Role Label</label><input name="label" value="{{ old('label', $role->label) }}" required></div>
    <div class="full">
        <h2>Permissions</h2>
        <div class="grid two">
            @foreach ($permissions as $permission)
                <label><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', $role->permissions->pluck('id')->all())))> {{ $permission->label }}</label>
            @endforeach
        </div>
    </div>
    <div class="full"><button class="btn" type="submit">Update Role</button></div>
</form>
@endsection
