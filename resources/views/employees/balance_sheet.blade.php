@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Employee Balance Sheet</h1>
        <div class="muted">{{ $employee->name }} | {{ $employee->designation ?? 'No designation' }} | {{ $year }}</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('expenses.create', ['employee_id' => $employee->id]) }}">Pay Salary</a>
        <a class="btn secondary" href="{{ route('expenses.create', ['expense_type' => 'other', 'category' => 'bonus', 'employee_id' => $employee->id]) }}">Pay Bonus</a>
        <a class="btn light" href="{{ route('employees.show', $employee) }}">Back</a>
    </div>
</div>

<form method="get" class="card actions" style="margin-bottom:16px">
    <input type="number" name="year" value="{{ $year }}" min="2000" max="2100">
    <button class="btn secondary" type="submit">View Year</button>
    <a class="btn light" href="{{ route('employees.balance-sheet', $employee) }}">Current Year</a>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Salary Payable</span><strong>{{ number_format($salaryPayable, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Salary Paid</span><strong>{{ number_format($salaryPaid, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Salary Due</span><strong>{{ number_format($salaryDue, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Salary Advance</span><strong>{{ number_format($salaryAdvance, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Net Due</span><strong>{{ number_format($netDue, 2) }}</strong></div>
</div>

<div class="grid two" style="margin-bottom:16px">
    <div class="card">
        <h2>Bonus Summary</h2>
        <p><strong>Rule:</strong> {{ $employee->yearly_bonus_count }} bonus x {{ number_format($employee->bonus_percent, 2) }}%</p>
        <p><strong>Single Bonus:</strong> {{ number_format($singleBonusAmount, 2) }}</p>
        <p><strong>Entitlement:</strong> {{ number_format($yearlyBonusEntitlement, 2) }}</p>
        <p><strong>Paid:</strong> {{ number_format($bonusPaid, 2) }}</p>
        <p><strong>Due:</strong> {{ number_format($bonusDue, 2) }}</p>
    </div>
    <div class="card">
        <h2>Net Position</h2>
        <p><strong>Total Payable:</strong> {{ number_format($netPayable, 2) }}</p>
        <p><strong>Total Paid:</strong> {{ number_format($netPaid, 2) }}</p>
        <p><strong>Total Due:</strong> {{ number_format($netDue, 2) }}</p>
        <p><strong>Advance Balance:</strong> {{ number_format($netAdvance, 2) }}</p>
        <p><strong>Opening Salary Balance:</strong> {{ $openingSalaryBalance >= 0 ? 'Due ' : 'Advance ' }}{{ number_format(abs($openingSalaryBalance), 2) }}</p>
        <p><strong>Closing Salary Balance:</strong> {{ $closingSalaryBalance >= 0 ? 'Due ' : 'Advance ' }}{{ number_format(abs($closingSalaryBalance), 2) }}</p>
        <p><strong>Current Salary:</strong> {{ number_format($employee->current_salary, 2) }}</p>
        <p><strong>Salary Effective From:</strong> {{ $employee->salary_effective_from?->format('Y-m-d') ?? 'N/A' }}</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <h2>Monthly Salary Balance</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Opening</th>
                <th>Payable Salary</th>
                <th>Paid</th>
                <th>Due</th>
                <th>Advance</th>
                <th>Closing</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['month_name'] }}</td>
                    <td>{{ $row['opening_balance'] >= 0 ? 'Due ' : 'Advance ' }}{{ number_format(abs($row['opening_balance']), 2) }}</td>
                    <td>{{ number_format($row['payable'], 2) }}</td>
                    <td>{{ number_format($row['paid'], 2) }}</td>
                    <td>{{ number_format($row['due'], 2) }}</td>
                    <td>{{ number_format($row['advance'], 2) }}</td>
                    <td>{{ $row['closing_balance'] >= 0 ? 'Due ' : 'Advance ' }}{{ number_format(abs($row['closing_balance']), 2) }}</td>
                    <td>
                        <span class="badge {{ $row['status'] === 'paid' ? 'paid' : ($row['status'] === 'partial' ? 'pending' : 'due') }}">
                            {{ str_replace('_', ' ', $row['status']) }}
                        </span>
                    </td>
                    <td>
                        @if ($row['due'] > 0)
                            <a class="btn light" href="{{ route('expenses.create', ['employee_id' => $employee->id, 'salary_month' => $row['month']]) }}">Pay</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Bonus Payments</h2>
    <table>
        <thead><tr><th>Date</th><th>Reference</th><th>Amount</th><th>Method</th><th>Note</th></tr></thead>
        <tbody>
            @forelse ($bonusPayments as $payment)
                <tr data-href="{{ route('expenses.show', $payment) }}">
                    <td>{{ $payment->expense_date->format('Y-m-d') }}</td>
                    <td>{{ $payment->reference ?? 'Bonus #'.$payment->id }}</td>
                    <td>{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ ucfirst($payment->payment_method) }}</td>
                    <td>{{ $payment->note ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No bonus payments found for {{ $year }}.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
