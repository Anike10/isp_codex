@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Support Tickets</h1><div class="muted">Customer complaints and technician work</div></div>
    <a class="btn" href="{{ route('tickets.create') }}">Add Ticket</a>
</div>

<table>
    <thead><tr><th>Subject</th><th>Customer</th><th>Technician</th><th>Priority</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($tickets as $ticket)
        <tr data-href="{{ route('tickets.show', $ticket) }}">
            <td>{{ $ticket->subject }}</td>
            <td>{{ $ticket->customer->name }}</td>
            <td>{{ $ticket->technician?->name ?? 'Unassigned' }}</td>
            <td>{{ $ticket->priority }}</td>
            <td><span class="badge {{ $ticket->status }}">{{ $ticket->status }}</span></td>
        </tr>
    @empty
        <tr><td colspan="5">No tickets found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $tickets->links() }}</div>
@endsection
