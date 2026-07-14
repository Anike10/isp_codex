@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>In-house Issue / Return History</h1><div class="muted">Complete assignment lifecycle with employee, serial, issue, and return balances</div></div><a class="btn" href="{{ route('in-house-use.index') }}">Create Issue</a></div>
@include('in_house_use.reports._nav')

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Search Product</label><input name="search" value="{{ request('search') }}" placeholder="Product name or SKU"></div>
    <div><label>Employee</label><select name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int)request('employee_id') === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
    <div><label>Status</label><select name="status"><option value="">All</option><option value="outstanding" @selected(request('status') === 'outstanding')>Currently Holding</option><option value="returned" @selected(request('status') === 'returned')>Fully Returned</option></select></div>
    <div class="full actions"><button class="btn secondary" type="submit">Filter</button><a class="btn light" href="{{ route('in-house-use.report.history') }}">Reset</a></div>
</form>

@include('partials.per_page')
<table>
    <thead><tr><th>Date</th><th>Employee</th><th>Product</th><th>Source</th><th>Serials</th><th>Issued</th><th>Returned</th><th>Holding</th><th>Unit Value</th><th>Issued Value</th><th>Holding Value</th><th>Purpose</th><th>Action</th></tr></thead>
    <tbody>
    @forelse($assignments as $assignment)
        <tr data-href="{{ route('in-house-use.show', $assignment) }}"><td>{{ $assignment->assigned_at->format('Y-m-d') }}</td><td>{{ $assignment->employee->name }}</td><td>{{ $assignment->product->name }}<div class="muted">{{ $assignment->product->sku }}</div></td><td>{{ $assignment->source_condition === 'used' ? 'Used Stock' : 'New Stock' }}<div class="muted">{{ $assignment->warehouse->name }}</div></td><td>{{ $assignment->serial_numbers ?: ($assignment->serialless_quantity.' serial-less') }}</td><td>{{ $assignment->quantity }}</td><td>{{ $assignment->returnedQuantity() }}</td><td><span class="badge {{ $assignment->outstandingQuantity() > 0 ? 'pending' : '' }}">{{ $assignment->outstandingQuantity() }}</span></td><td>{{ number_format((float)$assignment->unit_price, 2) }}</td><td>{{ number_format((float)$assignment->total, 2) }}</td><td><strong>{{ number_format($assignment->outstandingValue(), 2) }}</strong></td><td>{{ $assignment->purpose ?? 'N/A' }}</td><td><a class="btn light" href="{{ route('in-house-use.show', $assignment) }}">View / Return</a></td></tr>
    @empty
        <tr><td colspan="13">No issue record found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $assignments->links() }}</div>
@endsection
