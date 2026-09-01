@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Ticket</h1><div class="muted">Create support request or party complaint</div></div>
    <a class="btn light" href="{{ route('tickets.index') }}">Back</a>
</div>

<form method="post" action="{{ route('tickets.store') }}" class="card form-grid">
    @csrf
    <div>
        <label>Party</label>
        <select name="customer_id" data-search-placeholder="পার্টির নাম, সংযোগ আইডি বা ধরন লিখুন" required>
            <option value="">Select party</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                    {{ $customer->name }} - {{ $customer->connection_id ?? 'No connection' }}
                    @if ($customer->is_customer || $customer->is_vendor)
                        - {{ collect([$customer->is_customer ? 'Customer' : null, $customer->is_vendor ? 'Vendor' : null])->filter()->implode(' + ') }}
                    @endif
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Technician</label>
        <select name="assigned_to" data-search-placeholder="Technician-এর নাম লিখুন">
            <option value="">Unassigned</option>
            @foreach ($technicians as $technician)
                <option value="{{ $technician->id }}" @selected((string) old('assigned_to') === (string) $technician->id)>{{ $technician->name }}</option>
            @endforeach
        </select>
    </div>
    <div><label>Subject</label><input name="subject" value="{{ old('subject') }}" required></div>
    <div>
        <label>Priority</label>
        <select name="priority" required>
            <option value="low" @selected(old('priority') === 'low')>Low</option>
            <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
            <option value="high" @selected(old('priority') === 'high')>High</option>
            <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
        </select>
    </div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="open" @selected(old('status', 'open') === 'open')>Open</option>
            <option value="processing" @selected(old('status') === 'processing')>Processing</option>
            <option value="resolved" @selected(old('status') === 'resolved')>Resolved</option>
            <option value="closed" @selected(old('status') === 'closed')>Closed</option>
        </select>
    </div>
    <div class="full"><label>Description</label><textarea name="description" required>{{ old('description') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Save Ticket</button></div>
</form>
@endsection
