@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Fleet Reports</h1><div class="muted">Select a report; each report has its own filters, totals, and pages</div></div><a class="btn light" href="{{ route('fleet.index') }}">Vehicles</a></div>
@include('fleet.reports._nav')

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
    <a class="card" href="{{ route('fleet.reports.expenses') }}">
        <h2>Vehicle Expense Report</h2>
        <p class="muted">Vehicle-wise totals and itemized Fuel, Oil, Parts, Toll and miscellaneous costs.</p>
        <strong>{{ $expenseCount }} entries →</strong>
    </a>
    <a class="card" href="{{ route('fleet.reports.maintenance') }}">
        <h2>Maintenance Report</h2>
        <p class="muted">Check, change, service and repair history with mileage, cost and vendor details.</p>
        <strong>{{ $maintenanceCount }} logs | {{ $dueCount }} due →</strong>
    </a>
    <a class="card" href="{{ route('fleet.reports.duty-history') }}">
        <h2>Staff Duty History Report</h2>
        <p class="muted">Driver, Helper and Supervisor duty periods, including current and previous assignments.</p>
        <strong>{{ $dutyCount }} duty records →</strong>
    </a>
</div>
@endsection
