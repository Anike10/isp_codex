@extends('layouts.app')

@section('content')
@php
    $methodLabels = [
        'cash' => 'Cash',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'bank' => 'Bank',
    ];
@endphp

<div class="topbar">
    <div>
        <h1>{{ $types[$expense->expense_type] ?? ucfirst($expense->expense_type) }} Details</h1>
        <div class="muted">{{ $expense->expense_date->format('Y-m-d') }} · {{ number_format($expense->amount, 2) }}</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('expenses.create') }}">Add Expense</a>
        <a class="btn light" href="{{ route('expenses.index') }}">Back</a>
    </div>
</div>

<div class="grid two">
    <div class="card">
        <h2>Expense Information</h2>
        <p><strong>Type:</strong> {{ $types[$expense->expense_type] ?? ucfirst($expense->expense_type) }}</p>
        <p><strong>Category:</strong> {{ $categories[$expense->category] ?? ucfirst($expense->category) }}</p>
        <p><strong>Amount:</strong> {{ number_format($expense->amount, 2) }}</p>
        <p><strong>Date:</strong> {{ $expense->expense_date->format('Y-m-d') }}</p>
        <p><strong>Reference:</strong> {{ $expense->reference ?? 'N/A' }}</p>
    </div>

    <div class="card">
        <h2>Payment</h2>
        <p><strong>Method:</strong> {{ $methodLabels[$expense->payment_method] ?? ucfirst($expense->payment_method) }}</p>
        <p><strong>Account:</strong> {{ $expense->account ? $expense->account->account_name.' - '.$expense->account->account_number : 'Cash' }}</p>
        <p><strong>Entry By:</strong> {{ $expense->entry_by ?? 'N/A' }}</p>
    </div>

    @if ($expense->expense_type === 'salary')
        <div class="card">
            <h2>Employee</h2>
            <p><strong>Name:</strong> {{ $expense->employee_name }}</p>
            <p><strong>Designation:</strong> {{ $expense->employee_designation ?? 'N/A' }}</p>
            <p><strong>Salary Month:</strong> {{ $expense->salary_month }}</p>
        </div>
    @endif

    <div class="card">
        <h2>Note</h2>
        <p>{{ $expense->note ?: 'No note added.' }}</p>
    </div>
</div>
@endsection
