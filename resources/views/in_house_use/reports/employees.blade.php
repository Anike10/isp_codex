@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Employee Asset Report</h1><div class="muted">Employee-wise issued, returned, and currently-held products</div></div><a class="btn" href="{{ route('in-house-use.index') }}">Create Issue</a></div>
@include('in_house_use.reports._nav')

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Employee</label><select name="employee_id"><option value="">All employees</option>@foreach($employeeOptions as $employee)<option value="{{ $employee->id }}" @selected((int)request('employee_id') === $employee->id)>{{ $employee->name }}{{ $employee->designation ? ' - '.$employee->designation : '' }}</option>@endforeach</select></div>
    <div class="full actions"><button class="btn secondary" type="submit">Show Report</button><a class="btn light" href="{{ route('in-house-use.report.employees') }}">Reset</a></div>
</form>

<section class="card" style="margin-bottom:16px">
    <h2>Employee Summary</h2>
    <table>
        <thead><tr><th>Employee</th><th>Designation</th><th>Product Types</th><th>Total Issued</th><th>Returned</th><th>Currently Holding</th><th>New / Used Issues</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($employees as $employee)
            @php
                $issued = (int)$employee->assetAssignments->sum('quantity');
                $returned = (int)$employee->assetAssignments->flatMap->returns->sum('quantity');
                $holding = max(0, $issued - $returned);
                $newIssues = (int)$employee->assetAssignments->where('source_condition', 'new')->sum('quantity');
                $usedIssues = (int)$employee->assetAssignments->where('source_condition', 'used')->sum('quantity');
            @endphp
            <tr><td><strong>{{ $employee->name }}</strong></td><td>{{ $employee->designation ?? 'N/A' }}</td><td>{{ $employee->assetAssignments->pluck('product_id')->unique()->count() }}</td><td>{{ $issued }}</td><td>{{ $returned }}</td><td><span class="badge {{ $holding > 0 ? 'pending' : '' }}">{{ $holding }}</span></td><td>{{ $newIssues }} / {{ $usedIssues }}</td><td><a class="btn light" href="{{ route('in-house-use.report.employees', ['employee_id' => $employee->id]) }}">Details</a></td></tr>
        @empty
            <tr><td colspan="8">No employee asset record found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card">
    <h2>Product Details</h2>
    <table>
        <thead><tr><th>Employee</th><th>Issue Date</th><th>Product</th><th>Serials</th><th>Issued</th><th>Returned</th><th>Holding</th><th>Purpose</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($employees->flatMap->assetAssignments->sortByDesc('assigned_at') as $assignment)
            <tr><td>{{ $assignment->employee?->name ?? $employees->firstWhere('id', $assignment->employee_id)?->name }}</td><td>{{ $assignment->assigned_at->format('Y-m-d') }}</td><td>{{ $assignment->product->name }}<div class="muted">{{ $assignment->source_condition === 'used' ? 'Used stock' : 'New stock' }}</div></td><td>{{ $assignment->serial_numbers ?: ($assignment->serialless_quantity.' serial-less') }}</td><td>{{ $assignment->quantity }}</td><td>{{ $assignment->returnedQuantity() }}</td><td>{{ $assignment->outstandingQuantity() }}</td><td>{{ $assignment->purpose ?? 'N/A' }}</td><td><a class="btn light" href="{{ route('in-house-use.show', $assignment) }}">View / Return</a></td></tr>
        @empty
            <tr><td colspan="9">No product issue found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
