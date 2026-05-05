@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Users</h1><div class="muted">Login users, roles, and direct permissions</div></div>
    <a class="btn" href="{{ route('users.create') }}">Add User</a>
</div>

<table>
    <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th></th></tr></thead>
    <tbody>
    @forelse ($users as $user)
        <tr data-href="{{ route('users.edit', $user) }}">
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->roles->pluck('label')->join(', ') ?: 'No role' }}</td>
            <td><a class="btn light" href="{{ route('users.edit', $user) }}">Edit</a></td>
        </tr>
    @empty
        <tr><td colspan="4">No users found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $users->links() }}</div>
@endsection
