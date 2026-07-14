@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $employee->name }}</h1>
        <div class="muted">{{ $employee->designation ?? 'No designation' }} | {{ ucfirst($employee->status) }}</div>
    </div>
    <div class="actions">
        @if (auth()->user()?->hasPermission('manage_products'))
            <a class="btn secondary" href="{{ route('in-house-use.index', ['employee_id' => $employee->id]) }}">In-house Assets</a>
        @endif
        <a class="btn secondary" href="{{ route('expenses.create', ['employee_id' => $employee->id]) }}">Pay Salary</a>
        <a class="btn secondary" href="{{ route('expenses.create', ['expense_type' => 'other', 'category' => 'bonus', 'employee_id' => $employee->id]) }}">Pay Bonus</a>
        <a class="btn" href="{{ route('employees.balance-sheet', $employee) }}">Balance Sheet</a>
        <a class="btn light" href="{{ route('employees.edit', $employee) }}">Edit</a>
        <a class="btn light" href="{{ route('employees.index') }}">Back</a>
    </div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Current Salary</span><strong>{{ number_format($employee->current_salary, 2) }}</strong></div>
    <div class="card stat"><span class="muted">{{ $salaryMonth }} Salary Due</span><strong>{{ number_format($monthlySalaryDue, 2) }}</strong></div>
    <div class="card stat"><span class="muted">{{ $salaryMonth }} Advance</span><strong>{{ number_format($monthlySalaryAdvance, 2) }}</strong></div>
    <div class="card stat"><span class="muted">{{ $bonusYear }} Bonus Due</span><strong>{{ number_format($bonusDue, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Paid Salary Total</span><strong>{{ number_format($paidSalaryTotal, 2) }}</strong></div>
</div>

<form method="get" class="card actions" style="margin-bottom:16px">
    <input type="month" name="salary_month" value="{{ $salaryMonth }}">
    <input type="number" name="bonus_year" value="{{ $bonusYear }}" min="2000" max="2100">
    <button class="btn secondary" type="submit">Check Status</button>
    <a class="btn light" href="{{ route('employees.show', $employee) }}">Reset</a>
</form>

@if (auth()->user()?->hasPermission('manage_products'))
    @php
        $assetIssued = (int) $employee->assetAssignments->sum('quantity');
        $assetReturned = (int) $employee->assetAssignments->flatMap->returns->sum('quantity');
    @endphp
    <section class="card" style="margin-bottom:16px">
        <div class="topbar">
            <div><h2>In-house Assets</h2><div class="muted">Issued {{ $assetIssued }} | Returned {{ $assetReturned }} | Currently holding {{ max(0, $assetIssued - $assetReturned) }}</div></div>
            <a class="btn secondary" href="{{ route('in-house-use.index', ['employee_id' => $employee->id]) }}">Issue / View All</a>
        </div>
        <table>
            <thead><tr><th>Product</th><th>Issued</th><th>Returned</th><th>Holding</th><th>Purpose</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($employee->assetAssignments->take(10) as $assignment)
                <tr><td>{{ $assignment->product->name }}</td><td>{{ $assignment->quantity }}</td><td>{{ $assignment->returnedQuantity() }}</td><td>{{ $assignment->outstandingQuantity() }}</td><td>{{ $assignment->purpose ?? 'N/A' }}</td><td><a class="btn light" href="{{ route('in-house-use.show', $assignment) }}">View / Return</a></td></tr>
            @empty
                <tr><td colspan="6">No in-house asset assigned.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endif

<div class="grid two" style="margin-bottom:16px">
    <div class="card">
        <h2>Salary Status</h2>
        <p><strong>Month:</strong> {{ $salaryMonth }}</p>
        <p><strong>Opening Balance:</strong> {{ $monthlyOpeningBalance >= 0 ? 'Due ' : 'Advance ' }}{{ number_format(abs($monthlyOpeningBalance), 2) }}</p>
        <p><strong>Payable Salary:</strong> {{ number_format($monthlyPayableSalary, 2) }}</p>
        <p><strong>Paid:</strong> {{ number_format($monthlySalaryPaid, 2) }}</p>
        <p><strong>Remaining Due:</strong> {{ number_format($monthlySalaryDue, 2) }}</p>
        <p><strong>Advance Balance:</strong> {{ number_format($monthlySalaryAdvance, 2) }}</p>
        <p><strong>Closing Balance:</strong> {{ $monthlyClosingBalance >= 0 ? 'Due ' : 'Advance ' }}{{ number_format(abs($monthlyClosingBalance), 2) }}</p>
        <p><strong>Effective From:</strong> {{ $employee->salary_effective_from?->format('Y-m-d') ?? 'N/A' }}</p>
    </div>

    <div class="card">
        <h2>Bonus Status</h2>
        <p><strong>Year:</strong> {{ $bonusYear }}</p>
        <p><strong>Bonus Rule:</strong> {{ $employee->yearly_bonus_count }} bonus x {{ number_format($employee->bonus_percent, 2) }}%</p>
        <p><strong>Single Bonus Amount:</strong> {{ number_format($singleBonusAmount, 2) }}</p>
        <p><strong>Yearly Entitlement:</strong> {{ number_format($yearlyBonusEntitlement, 2) }}</p>
        <p><strong>Paid:</strong> {{ number_format($bonusPaid, 2) }} @if ($employee->yearly_bonus_count > 0) (approx {{ $bonusPaidCount }}/{{ $employee->yearly_bonus_count }}) @endif</p>
        <p><strong>Remaining:</strong> {{ number_format($bonusDue, 2) }}</p>
    </div>
</div>

<div class="grid two" style="margin-bottom:16px">
    <form method="post" action="{{ route('employees.salary-revisions.store', $employee) }}" class="card form-grid">
        @csrf
        <div class="full"><h2>Add Salary Increment</h2></div>
        <div><label>Old Salary</label><input value="{{ number_format($employee->current_salary, 2, '.', '') }}" disabled></div>
        <div><label>New Salary</label><input type="number" step="0.01" min="0" name="new_salary" required></div>
        <div><label>Effective From</label><input type="date" name="effective_from" value="{{ now()->toDateString() }}" required></div>
        <div><label>Reason</label><input name="reason" placeholder="Annual increment, promotion"></div>
        <div class="full"><button class="btn" type="submit">Save Increment</button></div>
    </form>

    <div class="card">
        <h2>Employee Details</h2>
        <p><strong>Phone:</strong> {{ $employee->phone ?? 'N/A' }}</p>
        <p><strong>Join Date:</strong> {{ $employee->join_date?->format('Y-m-d') ?? 'N/A' }}</p>
        <p><strong>Bonus Estimate:</strong> {{ number_format($yearlyBonusEntitlement, 2) }} / year</p>
        <p><strong>Note:</strong> {{ $employee->note ?: 'No note added.' }}</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <h2>Salary Increment History</h2>
    <table>
        <thead><tr><th>Effective From</th><th>Old Salary</th><th>New Salary</th><th>Increase</th><th>Reason</th></tr></thead>
        <tbody>
            @forelse ($employee->salaryRevisions as $revision)
                <tr>
                    <td>{{ $revision->effective_from->format('Y-m-d') }}</td>
                    <td>{{ number_format($revision->old_salary, 2) }}</td>
                    <td>{{ number_format($revision->new_salary, 2) }}</td>
                    <td>{{ number_format((float) $revision->new_salary - (float) $revision->old_salary, 2) }}</td>
                    <td>{{ $revision->reason ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No salary revisions recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Recent Salary & Bonus Payments</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Month</th><th>Amount</th><th>Method</th><th>Note</th></tr></thead>
        <tbody>
            @forelse ($employee->expenses as $expense)
                <tr data-href="{{ route('expenses.show', $expense) }}">
                    <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                    <td>{{ $expense->category === 'bonus' ? 'Bonus' : 'Salary' }}</td>
                    <td>{{ $expense->salary_month ?? 'N/A' }}</td>
                    <td>{{ number_format($expense->amount, 2) }}</td>
                    <td>{{ ucfirst($expense->payment_method) }}</td>
                    <td>{{ $expense->note ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No salary or bonus payments recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
