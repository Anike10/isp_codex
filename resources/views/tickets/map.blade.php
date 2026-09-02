@extends('layouts.app')

@section('content')
@php
    $canOpenPartyLedger = auth()->user()?->hasPermission('manage_payment_accounts') || auth()->user()?->hasPermission('manage_customers');
    $rx = static fn ($v) => $v === null ? '—' : number_format((float) $v, 2);
    $oltOnu = $onu
        ? trim(($onu->olt_name ?: '—').' - '.($onu->pon_port ?? '—').'/'.($onu->onu_id ?? '—'))
        : '—';
    $hasLocation = is_numeric($customer->map_latitude) && is_numeric($customer->map_longitude);
@endphp
<style>
    .ticket-map-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 16px; }
    .ticket-map-cards p { margin: 4px 0; }
    #partyLocationPicker { min-height: 60vh; }
</style>

<div class="topbar">
    <div>
        <h1>Ticket #{{ $ticket->id }} — Map &amp; Details</h1>
        <div class="muted">{{ $customer->name }} · {{ $ticket->created_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="actions" style="gap:8px">
        <a class="btn light" href="{{ route('tickets.index') }}">Back to list</a>
        <a class="btn secondary" href="{{ route('tickets.show', $ticket) }}">Open full ticket</a>
    </div>
</div>

<div class="ticket-map-cards">
    <section class="card">
        <h2>Ticket details</h2>
        <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
        <p><strong>Status:</strong> <span class="badge {{ $ticket->status }}">{{ $ticket->status }}</span></p>
        <p><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</p>
        <p><strong>Authorized:</strong> {{ $ticket->technician?->name ?? 'Unassigned' }}</p>
        <p><strong>RX/Update (dBm):</strong> {{ $rx($ticket->rx_power_on_create) }} / {{ $rx($ticket->rx_power_on_update) }}</p>
        <p><strong>Replies:</strong> {{ $ticket->replies->count() }}</p>
        <p style="white-space:pre-wrap;color:#475467;margin-top:8px">{{ \Illuminate\Support\Str::limit($ticket->description, 400) }}</p>
    </section>
    <section class="card">
        <h2>Party details</h2>
        <p><strong>Name:</strong>
            @if ($canOpenPartyLedger)
                <a href="{{ route('accounting.ledger', ['customer_id' => $customer->id]) }}">{{ $customer->name }}</a>
            @else
                {{ $customer->name }}
            @endif
        </p>
        <p><strong>Mobile:</strong> {{ $customer->phone ?: '—' }}</p>
        <p><strong>Connection ID:</strong> {{ $customer->connection_id ?: '—' }}</p>
        <p><strong>MikroTik username:</strong> {{ $customer->mikrotik_username ?: '—' }}</p>
        <p><strong>Address:</strong> {{ $customer->address ?: '—' }}</p>
        <p><strong>OLT / ONU:</strong> {{ $oltOnu }}</p>
        <p><strong>Map location:</strong> {{ $hasLocation ? 'Saved' : 'Not set for this party' }}</p>
    </section>
</div>

@include('customers.partials.map_location', ['customer' => $customer, 'editable' => false])
@endsection
