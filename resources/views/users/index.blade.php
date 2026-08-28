@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Users</h1><div class="muted">Login users, roles, and direct permissions</div></div>
    <a class="btn" href="{{ route('users.create') }}">Add User</a>
</div>

@include('partials.per_page')

@php
    $viewerIsSuperAdmin = (bool) auth()->user()?->isSuperAdmin();
@endphp

<table>
    <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Access level</th><th></th></tr></thead>
    <tbody>
    @forelse ($users as $user)
        @php($canManageTarget = ! $user->is_super_admin || $viewerIsSuperAdmin)
        <tr @if ($canManageTarget) data-href="{{ route('users.edit', $user) }}" @endif>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->roles->pluck('label')->join(', ') ?: 'No role' }}</td>
            <td>
                @if ($user->is_super_admin)
                    <span class="badge active">Super admin</span>
                @else
                    <span class="muted">Standard</span>
                @endif
            </td>
            <td>
                <div class="actions">
                    @if ($canManageTarget)
                        <a class="btn light" href="{{ route('users.edit', $user) }}">Edit</a>
                    @else
                        <span class="badge">Protected</span>
                    @endif
                    @if ($viewerIsSuperAdmin)
                        <form method="post" action="{{ route('users.super-admin.update', $user) }}"
                              onsubmit="return confirm('{{ $user->is_super_admin ? 'Remove super admin from' : 'Make super admin:' }} {{ addslashes($user->name) }}?')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_super_admin" value="{{ $user->is_super_admin ? 0 : 1 }}">
                            <button class="btn {{ $user->is_super_admin ? 'danger' : 'secondary' }}" type="submit">
                                {{ $user->is_super_admin ? 'Revoke super admin' : 'Make super admin' }}
                            </button>
                        </form>
                    @endif
                    @if (! $canManageTarget)
                        {{-- A standard user manager cannot alter a super-admin login. --}}
                    @elseif (auth()->id() === $user->id)
                        <span class="badge active">Current user</span>
                    @else
                        <form method="post" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete user {{ addslashes($user->name) }}? This login will stop working immediately.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn danger" type="submit">Delete</button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">No users found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $users->links() }}</div>
@endsection
