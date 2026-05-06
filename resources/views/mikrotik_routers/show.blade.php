@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $mikrotikRouter->name }}</h1>
        <div class="muted">{{ $mikrotikRouter->ip_address }}:{{ $mikrotikRouter->api_port }}</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('mikrotik-routers.edit', $mikrotikRouter) }}">Edit</a>
        <a class="btn light" href="{{ route('mikrotik-routers.index') }}">Back</a>
    </div>
</div>

<div class="card">
    <p><strong>IP Address:</strong> {{ $mikrotikRouter->ip_address }}</p>
    <p><strong>API Port:</strong> {{ $mikrotikRouter->api_port }}</p>
    <p><strong>Username:</strong> {{ $mikrotikRouter->username }}</p>
    <p><strong>Password:</strong> ********</p>
    <p><strong>Status:</strong> <span class="badge {{ $mikrotikRouter->status }}">{{ $mikrotikRouter->status }}</span></p>
    <p><strong>Notes:</strong> {{ $mikrotikRouter->notes ?? 'No notes' }}</p>
</div>
@endsection
