@extends('layouts.app')
@section('content')
<div class="topbar"><div><h1>IP Pools</h1><div class="muted">App-এ save করা সব IP pool</div></div></div>
<table><thead><tr><th>Pool</th><th>Ranges</th><th>Router</th><th>Status</th><th></th></tr></thead><tbody>@forelse($pools as $pool)<tr><td>{{ $pool->name }}</td><td>{{ $pool->ranges }}</td><td>{{ $pool->router?->name ?? 'No router' }}</td><td><span class="badge {{ $pool->status }}">{{ $pool->status }}</span></td><td>@if($pool->router)<a class="btn light" href="{{ route('mikrotik-routers.pools.index',$pool->router) }}">Manage</a>@endif</td></tr>@empty<tr><td colspan="5">No App IP pools saved yet.</td></tr>@endforelse</tbody></table><div style="margin-top:16px">{{ $pools->links() }}</div>
@endsection
