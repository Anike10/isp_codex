@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>MikroTik Routers</h1>
        <div class="muted">RouterOS API connection details</div>
    </div>
    <a class="btn" href="{{ route('mikrotik-routers.create') }}">Add Router</a>
</div>

@include('partials.per_page')

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>IP Address</th>
            <th>API Port</th>
            <th>Username</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($routers as $router)
            <tr data-href="{{ route('mikrotik-routers.show', $router) }}">
                <td>{{ $router->name }}</td>
                <td>{{ $router->ip_address }}</td>
                <td>{{ $router->api_port }}</td>
                <td>{{ $router->username }}</td>
                <td><span class="badge {{ $router->status }}">{{ $router->status }}</span></td>
                <td>
                    <div class="actions">
                        <a class="btn light" href="{{ route('mikrotik-routers.show', $router) }}">View</a>
                        <a class="btn secondary" href="{{ route('mikrotik-routers.edit', $router) }}">Edit</a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No MikroTik routers added yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $routers->links() }}</div>
@endsection
