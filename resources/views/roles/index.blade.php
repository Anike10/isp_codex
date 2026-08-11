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
            <td>
                <div class="actions">
                    <a class="btn light" href="{{ route('roles.edit', $role) }}">Edit</a>
                    @if ($role->name === 'admin')
                        <span class="badge active">Protected</span>
                    @else
                        <form method="post" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete role {{ addslashes($role->label) }}?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn danger" type="submit">Delete</button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="4">No roles found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $roles->links() }}</div>
@endsection
