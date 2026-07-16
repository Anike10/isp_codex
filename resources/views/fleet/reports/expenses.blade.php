@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Vehicle Expense Report</h1><div class="muted">Vehicle-wise totals and itemized daily/trip expenses</div></div><a class="btn light" href="{{ route('fleet.index') }}">Vehicles</a></div>
@include('fleet.reports._nav')

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Vehicle</label><select name="vehicle_id"><option value="">All vehicles</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((int)request('vehicle_id')===$vehicle->id)>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</option>@endforeach</select></div>
    <div><label>Driver / Employee</label><select name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int)request('employee_id')===$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
    <div><label>Category</label><select name="category"><option value="">All categories</option>@foreach(\App\Models\VehicleExpense::CATEGORIES as $key=>$label)<option value="{{ $key }}" @selected(request('category')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="full actions"><button class="btn">Apply Filters</button><a class="btn light" href="{{ route('fleet.reports.expenses') }}">Reset</a></div>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Total Expense</span><strong>{{ number_format((float)$expenseTotal,2) }}</strong></div>
    <div class="card stat"><span class="muted">Expense Entries</span><strong>{{ $expenseCount }}</strong></div>
    <div class="card stat"><span class="muted">Vehicles</span><strong>{{ $expenseByVehicle->count() }}</strong></div>
</div>

<section class="card" style="margin-bottom:16px"><h2>Vehicle-wise Total Expenses</h2><table><thead><tr><th>Vehicle</th><th>Entries</th><th>Total</th></tr></thead><tbody>
@forelse($expenseByVehicle as $row)<tr role="link" tabindex="0" title="Click to view vehicle" style="cursor:pointer" onclick="if (!event.target.closest('a,button')) window.location='{{ route('fleet.show', $row->vehicle) }}'" onkeydown="if (event.key === 'Enter') window.location='{{ route('fleet.show', $row->vehicle) }}'"><td><a href="{{ route('fleet.show',$row->vehicle) }}">{{ $row->vehicle->registration_no }} — {{ $row->vehicle->name }}</a></td><td>{{ $row->entry_count }}</td><td><strong>{{ number_format((float)$row->total_amount,2) }}</strong></td></tr>@empty<tr><td colspan="3">No expense data for the selected filters.</td></tr>@endforelse</tbody></table></section>

<section class="card"><h2>Itemized Expenses</h2>@include('partials.per_page')<table><thead><tr><th>Date</th><th>Vehicle</th><th>Category</th><th>Driver</th><th>Trip</th><th>Qty</th><th>Amount</th><th>Entered By</th></tr></thead><tbody>
@forelse($expenseRows as $row)<tr role="link" tabindex="0" title="Click to view expense details" style="cursor:pointer" onclick="if (!event.target.closest('a,button')) window.location='{{ route('fleet.expenses.show', $row) }}'" onkeydown="if (event.key === 'Enter') window.location='{{ route('fleet.expenses.show', $row) }}'"><td>{{ $row->expense_date->format('Y-m-d') }}</td><td>{{ $row->vehicle->registration_no }}</td><td><a href="{{ route('fleet.expenses.show', $row) }}">{{ \App\Models\VehicleExpense::CATEGORIES[$row->category] }}</a></td><td>{{ $row->employee?->name ?? 'N/A' }}</td><td>{{ $row->trip_reference ?? 'N/A' }}</td><td>{{ $row->quantity ? rtrim(rtrim(number_format((float)$row->quantity,3,'.',''), '0'), '.').' '.($row->unit ?? '') : 'N/A' }}</td><td>{{ number_format((float)$row->amount,2) }}</td><td>{{ $row->creator?->name ?? 'N/A' }}</td></tr>@empty<tr><td colspan="8">No expenses found.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $expenseRows->links() }}</div></section>
@endsection
