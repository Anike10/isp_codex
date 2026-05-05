@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Roles</h1><div class="muted">Permission groups for users</div></div>
    <a class="btn" href="{{ route('roles.create') }}">Add Role</a>
</div>

@include('partials.per_page')

<table>
    <thead><tr><th>Role</th><th>Users</th><th>Permissions</th><th></th></tr></thead>
    <tbody>
    @forelse ($roles as $role)
        <tr data-href="{{ route('roles.edit', $role) }}">
            <td>{{ $role->label }}<br><span class="muted">{{ $role->name }}</span></td>
            <td>{{ $role->users_count }}</td>
            <td>{{ $role->permissions->pluck('label')->join(', ') ?: 'No permissions' }}</td>
            <td><a class="btn light" href="{{ route('roles.edit', $role) }}">Edit</a></td>
        </tr>
    @empty
        <tr><td colspan="4">No roles found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $roles->links() }}</div>
@endsection
