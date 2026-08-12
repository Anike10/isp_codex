@extends('layouts.app')

@section('content')
@php
    $canOpenPaymentAccountLedger = auth()->user()?->hasPermission('manage_payment_accounts');
    $methodLabels = [
        'cash' => 'Cash',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'bank' => 'Bank',
    ];
@endphp

<div class="topbar">
    <div>
        <h1>Salary & Expenses</h1>
        <div class="muted">Employee salary and business expense details</div>
    </div>
    <a class="btn" href="{{ route('expenses.create') }}">Add Expense</a>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Salary Total</span><strong>{{ number_format($salaryTotal, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Other Expense</span><strong>{{ number_format($otherTotal, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Grand Total</span><strong>{{ number_format($grandTotal, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Salary Employees</span><strong>{{ $employeeCount }}</strong></div>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <input name="search" value="{{ request('search') }}" placeholder="Search reference, note, employee, account">
    <select name="expense_type">
        <option value="">All types</option>
        @foreach ($types as $value => $label)
            <option value="{{ $value }}" @selected(request('expense_type') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="category">
        <option value="">All categories</option>
        @foreach ($categories as $value => $label)
            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <input name="employee_name" value="{{ request('employee_name') }}" placeholder="Employee name">
    <input type="date" name="from" value="{{ request('from') }}">
    <input type="date" name="to" value="{{ request('to') }}">
    <div class="actions">
        <button class="btn secondary" type="submit">Filter</button>
        <a class="btn light" href="{{ route('expenses.index') }}">Reset</a>
    </div>
</form>

@include('partials.per_page')

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Category</th>
            <th>Employee</th>
            <th>Salary Month</th>
            <th>Method</th>
            <th>Amount</th>
            <th>Entry By</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($expenses as $expense)
            <tr data-href="{{ route('expenses.show', $expense) }}">
                <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                <td><span class="badge {{ $expense->expense_type === 'salary' ? 'active' : 'pending' }}">{{ $types[$expense->expense_type] ?? ucfirst($expense->expense_type) }}</span></td>
                <td>{{ $categories[$expense->category] ?? ucfirst($expense->category) }}</td>
                <td>
                    @if ($expense->employee)
                        <a href="{{ route('employees.show', $expense->employee) }}">{{ $expense->employee->name }}</a>
                    @else
                        {{ $expense->employee_name ?? 'N/A' }}
                    @endif
                    @if ($expense->employee_designation)
                        <div class="muted">{{ $expense->employee_designation }}</div>
                    @endif
                </td>
                <td>{{ $expense->salary_month ?? 'N/A' }}</td>
                <td>
                    {{ $methodLabels[$expense->payment_method] ?? ucfirst($expense->payment_method) }}
                    @if ($expense->account)
                        @if ($canOpenPaymentAccountLedger)
                            <div><a class="muted" href="{{ route('payment-accounts.show', $expense->account) }}">{{ $expense->account->account_name }}</a></div>
                        @else
                            <div class="muted">{{ $expense->account->account_name }}</div>
                        @endif
                    @elseif ($expense->payment_method === 'cash')
                        @if ($canOpenPaymentAccountLedger)
                            <div><a class="muted" href="{{ route('payment-accounts.cash-ledger') }}">Cash Ledger</a></div>
                        @else
                            <div class="muted">Cash</div>
                        @endif
                    @endif
                </td>
                <td>{{ number_format($expense->amount, 2) }}</td>
                <td>{{ $expense->entry_by ?? 'N/A' }}</td>
                <td>
                    <div class="actions">
                        <a class="btn light" href="{{ route('expenses.show', $expense) }}">View</a>
                        <a class="btn light" href="{{ route('expenses.voucher', $expense) }}">Voucher</a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9">No salary or expense records found.</td></tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $expenses->links() }}</div>
@endsection
