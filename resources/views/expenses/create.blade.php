@extends('layouts.app')

@section('content')
@php
    $accountsByMethod = $paymentAccounts
        ->groupBy('payment_method')
        ->map(fn ($accounts) => $accounts->map(fn ($account) => [
            'id' => $account->id,
            'label' => $account->account_name.' - '.$account->account_number,
        ])->values())
        ->toArray();
@endphp

<div class="topbar">
    <div>
        <h1>Add Salary / Expense</h1>
        <div class="muted">Record staff salary, rent, utility, maintenance, and other business costs</div>
    </div>
    <a class="btn light" href="{{ route('expenses.index') }}">Back</a>
</div>

<form method="post" action="{{ route('expenses.store') }}" class="card form-grid">
    @csrf
    <div>
        <label>Type</label>
        <select name="expense_type" id="expenseType" required>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('expense_type', 'salary') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Category</label>
        <select name="category" id="categorySelect" required>
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected(old('category', 'salary') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="salary-field">
        <label>Employee Name</label>
        <input name="employee_name" id="employeeName" value="{{ old('employee_name') }}">
    </div>
    <div class="salary-field">
        <label>Designation</label>
        <input name="employee_designation" value="{{ old('employee_designation') }}" placeholder="Technician, Manager, Support">
    </div>
    <div class="salary-field">
        <label>Salary Month</label>
        <input type="month" name="salary_month" id="salaryMonth" value="{{ old('salary_month', now()->format('Y-m')) }}">
    </div>
    <div>
        <label>Amount</label>
        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
    </div>
    <div>
        <label>Payment Method</label>
        <select name="payment_method" id="paymentMethod" required>
            <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
            <option value="bkash" @selected(old('payment_method') === 'bkash')>bKash</option>
            <option value="nagad" @selected(old('payment_method') === 'nagad')>Nagad</option>
            <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
        </select>
    </div>
    <div id="accountSelectWrap">
        <label>Account</label>
        <select name="payment_account_id" id="paymentAccount">
            <option value="">Select account</option>
        </select>
    </div>
    <div>
        <label>Expense Date</label>
        <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required>
    </div>
    <div>
        <label>Reference</label>
        <input name="reference" value="{{ old('reference') }}" placeholder="Voucher, bill, or transaction ID">
    </div>
    <div class="full">
        <label>Note</label>
        <textarea name="note">{{ old('note') }}</textarea>
    </div>
    <div class="full">
        <button class="btn" type="submit">Save Expense</button>
    </div>
</form>

<script>
const accountsByMethod = @json($accountsByMethod);
const oldAccountId = @json(old('payment_account_id'));
const expenseType = document.getElementById('expenseType');
const categorySelect = document.getElementById('categorySelect');
const salaryFields = document.querySelectorAll('.salary-field');
const employeeName = document.getElementById('employeeName');
const salaryMonth = document.getElementById('salaryMonth');
const methodSelect = document.getElementById('paymentMethod');
const accountWrap = document.getElementById('accountSelectWrap');
const accountSelect = document.getElementById('paymentAccount');

function refreshType() {
    const isSalary = expenseType.value === 'salary';

    salaryFields.forEach(field => field.style.display = isSalary ? 'block' : 'none');
    employeeName.required = isSalary;
    salaryMonth.required = isSalary;

    if (isSalary) {
        categorySelect.value = 'salary';
    }
}

function refreshAccounts() {
    const method = methodSelect.value;
    const needsAccount = method !== 'cash';

    accountWrap.style.display = needsAccount ? 'block' : 'none';
    accountSelect.required = needsAccount;
    accountSelect.innerHTML = '<option value="">Select account</option>';

    if (needsAccount) {
        (accountsByMethod[method] || []).forEach(account => {
            const option = document.createElement('option');
            option.value = account.id;
            option.textContent = account.label;
            option.selected = String(oldAccountId) === String(account.id);
            accountSelect.appendChild(option);
        });
    }
}

expenseType.addEventListener('change', refreshType);
methodSelect.addEventListener('change', refreshAccounts);
refreshType();
refreshAccounts();
</script>
@endsection
