@extends('layouts.app')

@section('content')
@php($defaults = $ticketDefaults ?? [])
<div class="topbar">
    <div>
        <h1>Add Ticket</h1>
        <div class="muted">
            {{ $isOnuDraft ?? false
                ? 'ONU signal details are prefilled. Review and edit them before saving.'
                : 'Create support request or party complaint' }}
        </div>
    </div>
    <a class="btn light" href="{{ $backUrl ?? route('tickets.index') }}">Back</a>
</div>

@if ($isOnuDraft ?? false)
    <div class="alert warning">এটি এখনো টিকিট হিসেবে সেভ হয়নি। প্রয়োজনমতো তথ্য সম্পাদনা করে <strong>Save Ticket</strong> চাপুন।</div>
@endif

<form method="post" action="{{ route('tickets.store') }}" class="card form-grid">
    @csrf
    <div>
        <label>Party</label>
        <select name="customer_id" data-search-placeholder="পার্টির নাম, সংযোগ আইডি বা ধরন লিখুন" required>
            <option value="">Select party</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $defaults['customer_id'] ?? '') === (string) $customer->id)>
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
                <option value="{{ $technician->id }}" @selected((string) old('assigned_to', $defaults['assigned_to'] ?? '') === (string) $technician->id)>{{ $technician->name }}</option>
            @endforeach
        </select>
    </div>
    <div><label>Subject</label><input name="subject" value="{{ old('subject', $defaults['subject'] ?? '') }}" required></div>
    <div>
        <label>Priority</label>
        <select name="priority" required>
            <option value="low" @selected(old('priority', $defaults['priority'] ?? 'normal') === 'low')>Low</option>
            <option value="normal" @selected(old('priority', $defaults['priority'] ?? 'normal') === 'normal')>Normal</option>
            <option value="high" @selected(old('priority', $defaults['priority'] ?? 'normal') === 'high')>High</option>
            <option value="urgent" @selected(old('priority', $defaults['priority'] ?? 'normal') === 'urgent')>Urgent</option>
        </select>
    </div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="open" @selected(old('status', $defaults['status'] ?? 'open') === 'open')>Open</option>
            <option value="processing" @selected(old('status', $defaults['status'] ?? 'open') === 'processing')>Processing</option>
            <option value="resolved" @selected(old('status', $defaults['status'] ?? 'open') === 'resolved')>Resolved</option>
            <option value="closed" @selected(old('status', $defaults['status'] ?? 'open') === 'closed')>Closed</option>
        </select>
    </div>
    <div class="full"><label>Description</label><textarea name="description" rows="14" required>{{ old('description', $defaults['description'] ?? '') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Save Ticket</button></div>
</form>
@endsection
