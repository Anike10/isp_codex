@extends('layouts.app')

@section('main_class', 'olt-onus-wide')

@section('content')
@php
    $canOpenPartyLedger = auth()->user()?->hasPermission('manage_payment_accounts') || auth()->user()?->hasPermission('manage_customers');
    $rxText = static fn ($value) => $value === null ? '—' : number_format((float) $value, 2);
@endphp
<div class="topbar">
    <div><h1>Support Tickets</h1><div class="muted">Party complaints and authorized staff work</div></div>
    <a class="btn" href="{{ route('tickets.create') }}">Add Ticket</a>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Subject, description, party, phone, connection ID, authorized"></div>
    <div><label>Priority</label><select name="priority"><option value="">All priorities</option>@foreach(['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
    <div><label>Status</label><select name="status"><option value="">Not closed (default)</option><option value="all" @selected(request('status') === 'all')>All statuses</option>@foreach(['open','processing','resolved','closed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
    <div><label>Authorized</label><select name="assigned"><option value="">All authorized</option><option value="unassigned" @selected(request('assigned') === 'unassigned')>Unassigned</option>@foreach($technicians as $technician)<option value="{{ $technician->id }}" @selected((string) request('assigned') === (string) $technician->id)>{{ $technician->name }}</option>@endforeach</select></div>
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('tickets.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>#</th><th>Party</th><th>Authorized</th><th class="col-center">RX/Update</th><th>Subject</th><th class="col-center">Status</th><th>Action</th></tr></thead>
    <tbody>
    @forelse ($tickets as $ticket)
        <tr data-href="{{ route('tickets.show', $ticket) }}">
            <td>{{ $tickets->firstItem() + $loop->index }}</td>
            <td>
                @if ($canOpenPartyLedger)
                    <a href="{{ route('accounting.ledger', ['customer_id' => $ticket->customer_id]) }}">{{ $ticket->customer->name }}</a>
                @else
                    {{ $ticket->customer->name }}
                @endif
            </td>
            <td>{{ $ticket->technician?->name ?? 'Unassigned' }}</td>
            <td class="col-center" @if($ticket->rx_power_updated_at) title="Last update Rx captured {{ $ticket->rx_power_updated_at->format('d/m/Y H:i') }}" @endif>
                {{ $rxText($ticket->rx_power_on_create) }} <span class="muted">/</span> {{ $rxText($ticket->rx_power_on_update) }}
            </td>
            <td><a href="{{ route('tickets.show', $ticket) }}"><strong>{{ $ticket->subject }}</strong></a></td>
            <td class="col-center"><span class="badge {{ $ticket->status }}">{{ $ticket->status }}</span></td>
            <td><a class="btn light" href="{{ route('tickets.show', $ticket) }}">Reply / Update</a></td>
        </tr>
    @empty
        <tr><td colspan="7">No tickets found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $tickets->links() }}</div>
@endsection
