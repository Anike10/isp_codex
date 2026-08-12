@extends('layouts.app')

@section('content')
@php
    $methodLabels = [
        'cash' => 'Cash',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'bank' => 'Bank',
    ];
    $typeLabel = $types[$expense->expense_type] ?? ucfirst($expense->expense_type);
    $categoryLabel = $categories[$expense->category] ?? ucfirst($expense->category);
    $isSalary = $expense->expense_type === 'salary';
    $isBonus = $expense->category === 'bonus';
@endphp

<style>
    .expense-highlight {
        display:grid;
        grid-template-columns:minmax(0, 1.4fr) repeat(3, minmax(160px, .7fr));
        gap:12px;
        margin-bottom:16px;
    }
    .highlight-card {
        background:#fff;
        border:1px solid var(--line);
        border-radius:8px;
        padding:16px;
    }
    .highlight-card.primary {
        background:#ecfdf3;
        border-color:#b7e4c7;
    }
    .highlight-label {
        display:block;
        color:var(--muted);
        font-size:12px;
        font-weight:700;
        text-transform:uppercase;
    }
    .highlight-value {
        display:block;
        margin-top:7px;
        font-size:24px;
        font-weight:800;
        color:var(--ink);
        line-height:1.2;
    }
    .highlight-card.primary .highlight-value {
        color:#05603a;
        font-size:34px;
    }
    .detail-list {
        display:grid;
        gap:10px;
    }
    .detail-row {
        display:flex;
        justify-content:space-between;
        gap:14px;
        padding:10px 0;
        border-bottom:1px solid #edf1f5;
    }
    .detail-row:last-child {
        border-bottom:0;
    }
    .detail-row span:first-child {
        color:var(--muted);
        font-weight:700;
    }
    .detail-row span:last-child {
        text-align:right;
        font-weight:700;
    }
    @media (max-width: 980px) {
        .expense-highlight { grid-template-columns:1fr 1fr; }
    }
    @media (max-width: 560px) {
        .expense-highlight { grid-template-columns:1fr; }
        .highlight-card.primary .highlight-value { font-size:28px; }
        .detail-row { flex-direction:column; gap:4px; }
        .detail-row span:last-child { text-align:left; }
    }
</style>

<div class="topbar">
    <div>
        <h1>{{ $typeLabel }} Details</h1>
        <div class="muted">{{ $expense->expense_date->format('d/m/Y') }} | {{ $categoryLabel }}</div>
    </div>
    <div class="actions">
        @if ($expense->employee)
            <a class="btn secondary" href="{{ route('employees.balance-sheet', $expense->employee) }}">Balance Sheet</a>
        @endif
        <a class="btn" href="{{ route('expenses.voucher', $expense) }}">Print Voucher</a>
        <a class="btn secondary" href="{{ route('expenses.create') }}">Add Expense</a>
        <a class="btn light" href="{{ route('expenses.index') }}">Back</a>
    </div>
</div>

<div class="expense-highlight">
    <div class="highlight-card primary">
        <span class="highlight-label">Amount</span>
        <span class="highlight-value">BDT {{ number_format($expense->amount, 2) }}</span>
    </div>
    <div class="highlight-card">
        <span class="highlight-label">Type</span>
        <span class="highlight-value">{{ $isBonus ? 'Bonus' : $typeLabel }}</span>
    </div>
    <div class="highlight-card">
        <span class="highlight-label">Payment</span>
        <span class="highlight-value">{{ $methodLabels[$expense->payment_method] ?? ucfirst($expense->payment_method) }}</span>
    </div>
    <div class="highlight-card">
        <span class="highlight-label">Date</span>
        <span class="highlight-value">{{ $expense->expense_date->format('d/m/Y') }}</span>
    </div>
</div>

<div class="grid two">
    <div class="card">
        <h2>Expense Information</h2>
        <div class="detail-list">
            <div class="detail-row"><span>Category</span><span>{{ $categoryLabel }}</span></div>
            <div class="detail-row"><span>Reference</span><span>{{ $expense->reference ?? 'N/A' }}</span></div>
            <div class="detail-row"><span>Entry By</span><span>{{ $expense->entry_by ?? 'N/A' }}</span></div>
            <div class="detail-row"><span>Created</span><span>{{ $expense->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</span></div>
        </div>
    </div>

    <div class="card">
        <h2>Payment Details</h2>
        <div class="detail-list">
            <div class="detail-row"><span>Method</span><span>{{ $methodLabels[$expense->payment_method] ?? ucfirst($expense->payment_method) }}</span></div>
            <div class="detail-row"><span>Account</span><span>{{ $expense->account ? $expense->account->account_name.' - '.$expense->account->account_number : 'Cash' }}</span></div>
            <div class="detail-row"><span>Amount</span><span>BDT {{ number_format($expense->amount, 2) }}</span></div>
        </div>
    </div>

    @if ($isSalary || $isBonus)
        <div class="card">
            <h2>{{ $isBonus ? 'Bonus Employee' : 'Salary Employee' }}</h2>
            <div class="detail-list">
                <div class="detail-row"><span>Name</span><span>{{ $expense->employee_name ?? 'N/A' }}</span></div>
                <div class="detail-row"><span>Designation</span><span>{{ $expense->employee_designation ?? 'N/A' }}</span></div>
                @if ($isSalary)
                    <div class="detail-row"><span>Salary Month</span><span>{{ $expense->salary_month ?? 'N/A' }}</span></div>
                @endif
                @if ($expense->employee)
                    <div class="detail-row"><span>Profile</span><span><a href="{{ route('employees.show', $expense->employee) }}">{{ $expense->employee->name }}</a></span></div>
                @endif
            </div>
        </div>
    @endif

    <div class="card">
        <h2>Note</h2>
        <p>{{ $expense->note ?: 'No note added.' }}</p>
    </div>
</div>
@endsection
