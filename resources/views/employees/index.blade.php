@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Employees</h1>
        <div class="muted">Salary, increment history, and yearly bonus rules</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('expenses.create') }}">Pay Salary</a>
        <a class="btn" href="{{ route('employees.create') }}">Add Employee</a>
    </div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Active Employees</span><strong>{{ $activeEmployees }}</strong></div>
    <div class="card stat"><span class="muted">Monthly Salary Total</span><strong>{{ number_format($totalMonthlySalary, 2) }}</strong></div>
    <div class="card stat"><span class="muted">{{ $salaryMonth }} Salary Due</span><strong>{{ number_format($salaryDueThisMonth, 2) }}</strong></div>
    <div class="card stat"><span class="muted">{{ $salaryMonth }} Advance</span><strong>{{ number_format($salaryAdvanceThisMonth, 2) }}</strong></div>
    <div class="card stat"><span class="muted">{{ $bonusYear }} Bonus Due</span><strong>{{ number_format($bonusDueThisYear, 2) }}</strong></div>
</div>

<form method="get" class="card actions" style="margin-bottom:16px">
    <input name="q" value="{{ request('q') }}" placeholder="Search employee">
    <input type="month" name="salary_month" value="{{ $salaryMonth }}">
    <input type="number" name="bonus_year" value="{{ $bonusYear }}" min="2000" max="2100" placeholder="Bonus year">
    <select name="status">
        <option value="">All status</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
    </select>
    <button class="btn secondary" type="submit">Filter</button>
    <a class="btn light" href="{{ route('employees.index') }}">Reset</a>
</form>

@include('partials.per_page')

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Designation</th>
            <th>Current Salary</th>
            <th>{{ $salaryMonth }} Salary</th>
            <th>{{ $bonusYear }} Bonus</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($employees as $employee)
            <tr data-href="{{ route('employees.show', $employee) }}">
                <td>
                    {{ $employee->name }}
                    @if ($employee->phone)
                        <div class="muted">{{ $employee->phone }}</div>
                    @endif
                </td>
                <td>{{ $employee->designation ?? 'N/A' }}@if($employee->fleet_role)<div class="muted">Fleet: {{ ucfirst($employee->fleet_role) }}</div>@endif</td>
                <td>{{ number_format($employee->current_salary, 2) }}</td>
                @php
                    $salaryPaid = (float) ($employee->selected_month_salary_paid ?? 0);
                    $salaryDue = (float) ($employee->selected_month_salary_due ?? 0);
                    $salaryAdvance = (float) ($employee->selected_month_salary_advance ?? 0);
                    $singleBonus = round((float) $employee->current_salary * (float) $employee->bonus_percent / 100, 2);
                    $bonusEntitled = $singleBonus * (int) $employee->yearly_bonus_count;
                    $bonusPaid = (float) ($employee->selected_year_bonus_paid ?? 0);
                    $bonusDue = max(0, $bonusEntitled - $bonusPaid);
                @endphp
                <td>
                    Paid {{ number_format($salaryPaid, 2) }}
                    <div class="muted">Due {{ number_format($salaryDue, 2) }}</div>
                    @if ($salaryAdvance > 0)
                        <div class="muted">Advance {{ number_format($salaryAdvance, 2) }}</div>
                    @endif
                </td>
                <td>
                    {{ $employee->yearly_bonus_count }} x {{ number_format($employee->bonus_percent, 2) }}%
                    <div class="muted">Due {{ number_format($bonusDue, 2) }}</div>
                </td>
                <td><span class="badge {{ $employee->status }}">{{ $employee->status }}</span></td>
                <td><a class="btn light" href="{{ route('employees.show', $employee) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="8">No employees added yet.</td></tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $employees->links() }}</div>
@endsection
