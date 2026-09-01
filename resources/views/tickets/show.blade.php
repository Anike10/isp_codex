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
    <p style="white-space:pre-wrap">{{ $ticket->description }}</p>
</section>

<section class="card" style="margin-top:16px">
    <h2>Update Status</h2>
    <form method="post" action="{{ route('tickets.status.update', $ticket) }}" class="form-grid">
        @csrf
        @method('patch')
        <div>
            <label>Status</label>
            <select name="status" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $ticket->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Technician</label>
            <select name="assigned_to">
                <option value="">Unassigned</option>
                @foreach ($technicians as $technician)
                    <option value="{{ $technician->id }}" @selected((string) old('assigned_to', $ticket->assigned_to) === (string) $technician->id)>{{ $technician->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="full"><label>Note (optional)</label><textarea name="note" placeholder="Reason for the change, work done, etc.">{{ old('note') }}</textarea></div>
        <div class="full"><button class="btn" type="submit">Save Update</button></div>
    </form>
</section>

<section class="card" style="margin-top:16px">
    <h2>Conversation</h2>
    @forelse ($ticket->replies as $reply)
        <div style="padding:10px 0; border-bottom:1px solid var(--line, #e6ebf2)">
            <div class="muted" style="font-size:12px">
                {{ $reply->user?->name ?? 'System' }} · {{ $reply->created_at->format('d/m/Y H:i') }}
            </div>
            @if ($reply->isStatusChange())
                <p style="margin:6px 0">
                    Status changed
                    <span class="badge {{ $reply->old_status }}">{{ $reply->old_status }}</span>
                    &rarr;
                    <span class="badge {{ $reply->new_status }}">{{ $reply->new_status }}</span>
                </p>
            @endif
            @if (filled($reply->body))
                <p style="margin:6px 0; white-space:pre-wrap">{{ $reply->body }}</p>
            @endif
        </div>
    @empty
        <p class="muted">No replies yet.</p>
    @endforelse

    <form method="post" action="{{ route('tickets.replies.store', $ticket) }}" style="margin-top:14px">
        @csrf
        <label>Add Reply</label>
        <textarea name="body" required placeholder="Write a reply...">{{ old('body') }}</textarea>
        <div class="actions" style="margin-top:10px"><button class="btn" type="submit">Send Reply</button></div>
    </form>
</section>
@endsection
