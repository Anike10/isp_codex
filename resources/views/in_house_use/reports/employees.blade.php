@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Employee Asset Report</h1><div class="muted">Employee-wise issued, returned, and currently-held products</div></div><a class="btn" href="{{ route('in-house-use.index') }}">Create Issue</a></div>
@include('in_house_use.reports._nav')

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div><label>Employee</label><select name="employee_id"><option value="">All employees</option>@foreach($employeeOptions as $employee)<option value="{{ $employee->id }}" @selected((int)request('employee_id') === $employee->id)>{{ $employee->name }}{{ $employee->designation ? ' - '.$employee->designation : '' }}</option>@endforeach</select></div>
    <div class="full actions"><button class="btn secondary" type="submit">Show Report</button><a class="btn light" href="{{ route('in-house-use.report.employees') }}">Reset</a></div>
</form>

<section class="card" style="margin-bottom:16px">
    <h2>Employee Summary</h2>
    <table>
        <thead><tr><th>Employee</th><th>Designation</th><th>Product Types</th><th>Total Issued</th><th>Returned</th><th>Currently Holding</th><th>Issued Value</th><th>Returned Value</th><th>Holding Value</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($employees as $employee)
            @php
                $issued = (int)$employee->assetAssignments->sum('quantity');
                $returned = (int)$employee->assetAssignments->flatMap->returns->sum('quantity');
                $holding = max(0, $issued - $returned);
                $newIssues = (int)$employee->assetAssignments->where('source_condition', 'new')->sum('quantity');
                $usedIssues = (int)$employee->assetAssignments->where('source_condition', 'used')->sum('quantity');
                $issuedValue = (float)$employee->assetAssignments->sum(fn($assignment) => (float)$assignment->total);
                $returnedValue = (float)$employee->assetAssignments->sum(fn($assignment) => $assignment->returnedValue());
                $holdingValue = max(0, $issuedValue - $returnedValue);
            @endphp
            <tr><td><strong>{{ $employee->name }}</strong><div class="muted">New {{ $newIssues }} / Used {{ $usedIssues }}</div></td><td>{{ $employee->designation ?? 'N/A' }}</td><td>{{ $employee->assetAssignments->pluck('product_id')->unique()->count() }}</td><td>{{ $issued }}</td><td>{{ $returned }}</td><td><span class="badge {{ $holding > 0 ? 'pending' : '' }}">{{ $holding }}</span></td><td>{{ number_format($issuedValue, 2) }}</td><td>{{ number_format($returnedValue, 2) }}</td><td><strong>{{ number_format($holdingValue, 2) }}</strong></td><td><a class="btn light" href="{{ route('in-house-use.report.employees', ['employee_id' => $employee->id]) }}">Details</a></td></tr>
        @empty
            <tr><td colspan="10">No employee asset record found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Product Details</h2>
    <table>
        <thead><tr><th>Employee</th><th>Issue Date</th><th>Product</th><th>Serials</th><th>Issued</th><th>Unit Value</th><th>Issued Value</th><th>Returned Value</th><th>Holding Value</th><th>Purpose</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($employees->flatMap->assetAssignments->sortByDesc('assigned_at') as $assignment)
            <tr><td>{{ $assignment->employee?->name ?? $employees->firstWhere('id', $assignment->employee_id)?->name }}</td><td>{{ $assignment->assigned_at->format('d/m/Y') }}</td><td>{{ $assignment->product->name }}<div class="muted">{{ $assignment->source_condition === 'used' ? 'Used stock' : 'New stock' }}</div></td><td>{{ $assignment->serial_numbers ?: ($assignment->serialless_quantity.' serial-less') }}</td><td>{{ $assignment->quantity }}<div class="muted">Returned {{ $assignment->returnedQuantity() }} / Holding {{ $assignment->outstandingQuantity() }}</div></td><td>{{ number_format((float)$assignment->unit_price, 2) }}</td><td>{{ number_format((float)$assignment->total, 2) }}</td><td>{{ number_format($assignment->returnedValue(), 2) }}</td><td><strong>{{ number_format($assignment->outstandingValue(), 2) }}</strong></td><td>{{ $assignment->purpose ?? 'N/A' }}</td><td><a class="btn light" href="{{ route('in-house-use.show', $assignment) }}">View / Return</a></td></tr>
        @empty
            <tr><td colspan="11">No product issue found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
