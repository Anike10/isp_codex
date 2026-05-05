@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Ticket</h1><div class="muted">Create support request or customer complaint</div></div>
    <a class="btn light" href="{{ route('tickets.index') }}">Back</a>
</div>

<form method="post" action="{{ route('tickets.store') }}" class="card form-grid">
    @csrf
    <div>
        <label>Customer</label>
        <select name="customer_id" required>
            <option value="">Select customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->connection_id }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Technician</label>
        <select name="assigned_to">
            <option value="">Unassigned</option>
            @foreach ($technicians as $technician)
                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
            @endforeach
        </select>
    </div>
    <div><label>Subject</label><input name="subject" value="{{ old('subject') }}" required></div>
    <div>
        <label>Priority</label>
        <select name="priority" required>
            <option value="low">Low</option>
            <option value="normal" selected>Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
        </select>
    </div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="open">Open</option>
            <option value="processing">Processing</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
        </select>
    </div>
    <div class="full"><label>Description</label><textarea name="description" required>{{ old('description') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Save Ticket</button></div>
</form>
@endsection
