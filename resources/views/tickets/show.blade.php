@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $ticket->subject }}</h1>
        <div class="muted">{{ $ticket->customer->name }} - {{ $ticket->created_at->format('d/m/Y') }}</div>
    </div>
    <a class="btn light" href="{{ route('tickets.index') }}">Back</a>
</div>

<div class="grid two">
    <section class="card">
        <h2>Ticket Details</h2>
        <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
        <p><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</p>
        <p><strong>Status:</strong> <span class="badge {{ $ticket->status }}">{{ $ticket->status }}</span></p>
        <p><strong>Technician:</strong> {{ $ticket->technician?->name ?? 'Unassigned' }}</p>
    </section>
    <section class="card">
        <h2>Party</h2>
        <p><strong>Name:</strong> {{ $ticket->customer->name }}</p>
        <p><strong>Phone:</strong> {{ $ticket->customer->phone }}</p>
        <p><strong>Connection:</strong> {{ $ticket->customer->connection_id }}</p>
    </section>
</div>

<section class="card" style="margin-top:16px">
    <h2>Description</h2>
    <p>{{ $ticket->description }}</p>
</section>
@endsection
