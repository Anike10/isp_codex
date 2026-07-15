@extends('layouts.app')
@section('content')
<div class="topbar"><div><h1>Print History</h1><div class="muted">Recorded only when the Print button is pressed.</div></div><a class="btn light" href="{{ route('organizations.index') }}">Organizations</a></div>
<table><thead><tr><th>Printed</th><th>Document</th><th>Organization</th><th>Printed By</th><th>IP</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td>{{ $log->printed_at->format('Y-m-d H:i:s') }}</td><td>{{ ucwords(str_replace('_', ' ', $log->document_type)) }}<br><strong>{{ $log->document_no ?: '#'.$log->printable_id }}</strong></td><td>{{ $log->organization->name }}</td><td>{{ $log->user?->name ?: $log->user_name ?: 'Unknown' }}</td><td>{{ $log->ip_address ?: 'N/A' }}</td></tr>@empty<tr><td colspan="5">No print history yet.</td></tr>@endforelse
</tbody></table><div style="margin-top:16px">{{ $logs->links() }}</div>
@endsection
