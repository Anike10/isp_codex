@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Role</h1><div class="muted">Create a role and choose permissions</div></div>
    <a class="btn light" href="{{ route('roles.index') }}">Back</a>
</div>

<form method="post" action="{{ route('roles.store') }}" class="card form-grid">
    @csrf
    <div><label>Role Key</label><input name="name" value="{{ old('name') }}" placeholder="manager" required></div>
    <div><label>Role Label</label><input name="label" value="{{ old('label') }}" placeholder="Manager" required></div>
    <div class="full">
        <h2>Permissions</h2>
        <div class="grid two">
            @foreach ($permissions as $permission)
                <label><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', [])))> {{ $permission->label }}</label>
            @endforeach
        </div>
    </div>
    <div class="full"><button class="btn" type="submit">Save Role</button></div>
</form>
@endsection
