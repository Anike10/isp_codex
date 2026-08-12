@extends('layouts.app')

@section('content')
@php
    $employeesForJs = $employees->mapWithKeys(fn ($employee) => [$employee->id => [
        'name' => $employee->name,
        'designation' => $employee->designation,
        'current_salary' => $employee->current_salary,
        'bonus_amount' => round((float) $employee->current_salary * (float) $employee->bonus_percent / 100, 2),
        'yearly_bonus_count' => $employee->yearly_bonus_count,
        'bonus_percent' => $employee->bonus_percent,
        'salary_effective_from' => $employee->salary_effective_from?->format('d/m/Y'),
    ]]);

    $accountsByMethod = $paymentAccounts
        ->groupBy('payment_method')
        ->map(fn ($accounts) => $accounts->map(fn ($account) => [
            'id' => $account->id,
            'label' => $account->account_name.' - '.$account->account_number,
        ])->values())
        ->toArray();
    $selectedPaymentMethod = old('payment_method', $paymentDefault['payment_method'] ?? 'cash');
    $selectedPaymentAccountId = old('payment_account_id', $paymentDefault['payment_account_id'] ?? null);
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
                <option value="{{ $value }}" @selected(old('expense_type', request('expense_type', 'salary')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Category</label>
        <select name="category" id="categorySelect" required>
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected(old('category', request('category', 'salary')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="employee-cost-field">
        <label>Employee</label>
        <select name="employee_id" id="employeeSelect">
            <option value="">Manual employee name</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((string) old('employee_id', request('employee_id')) === (string) $employee->id)>
                    {{ $employee->name }}{{ $employee->designation ? ' - '.$employee->designation : '' }} - Salary {{ number_format($employee->current_salary, 2) }}
                </option>
            @endforeach
        </select>
        <span class="muted" id="employeeSalaryHint">Select an employee to fill salary details automatically.</span>
    </div>
    <div class="employee-cost-field">
        <label>Employee Name</label>
        <input name="employee_name" id="employeeName" value="{{ old('employee_name') }}">
    </div>
    <div class="employee-cost-field">
        <label>Designation</label>
        <input name="employee_designation" id="employeeDesignation" value="{{ old('employee_designation') }}" placeholder="Technician, Manager, Support">
    </div>
    <div class="salary-field">
        <label>Salary Month</label>
        <input type="month" name="salary_month" id="salaryMonth" value="{{ old('salary_month', request('salary_month', now()->format('Y-m'))) }}">
    </div>
    <div>
        <label>Amount</label>
        <input type="number" step="0.01" min="0.01" name="amount" id="amountInput" value="{{ old('amount') }}" required>
    </div>
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <label>Payment Method</label>
            @include('partials.payment_default_checkbox')
        </div>
        <select name="payment_method" id="paymentMethod" required>
            <option value="cash" @selected($selectedPaymentMethod === 'cash')>Cash</option>
            <option value="bkash" @selected($selectedPaymentMethod === 'bkash')>bKash</option>
            <option value="nagad" @selected($selectedPaymentMethod === 'nagad')>Nagad</option>
            <option value="bank" @selected($selectedPaymentMethod === 'bank')>Bank</option>
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
const employeesById = @json($employeesForJs);
const oldAccountId = @json($selectedPaymentAccountId);
const expenseType = document.getElementById('expenseType');
const categorySelect = document.getElementById('categorySelect');
const employeeSelect = document.getElementById('employeeSelect');
const salaryFields = document.querySelectorAll('.salary-field');
const employeeCostFields = document.querySelectorAll('.employee-cost-field');
const employeeName = document.getElementById('employeeName');
const employeeDesignation = document.getElementById('employeeDesignation');
const salaryMonth = document.getElementById('salaryMonth');
const amountInput = document.getElementById('amountInput');
const employeeSalaryHint = document.getElementById('employeeSalaryHint');
const methodSelect = document.getElementById('paymentMethod');
const accountWrap = document.getElementById('accountSelectWrap');
const accountSelect = document.getElementById('paymentAccount');

function refreshType() {
    const isSalary = expenseType.value === 'salary';
    const isBonus = expenseType.value === 'other' && categorySelect.value === 'bonus';
    const isEmployeeCost = isSalary || isBonus;

    employeeCostFields.forEach(field => field.style.display = isEmployeeCost ? 'block' : 'none');
    salaryFields.forEach(field => field.style.display = isSalary ? 'block' : 'none');
    employeeName.required = isSalary;
    salaryMonth.required = isSalary;

    if (isSalary) {
        categorySelect.value = 'salary';
    }
}

function refreshEmployeeDetails() {
    const employee = employeesById[employeeSelect.value];

    if (! employee) {
        employeeSalaryHint.textContent = 'Select an employee to fill salary details automatically.';
        return;
    }

    employeeName.value = employee.name || '';
    employeeDesignation.value = employee.designation || '';

    if (! amountInput.value) {
        amountInput.value = categorySelect.value === 'bonus' ? (employee.bonus_amount || '') : (employee.current_salary || '');
    }

    employeeSalaryHint.textContent = `Salary ${Number(employee.current_salary || 0).toFixed(2)} from ${employee.salary_effective_from || 'N/A'} | Bonus ${employee.yearly_bonus_count} x ${Number(employee.bonus_percent || 0).toFixed(2)}% = ${Number(employee.bonus_amount || 0).toFixed(2)} each.`;
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
categorySelect.addEventListener('change', () => {
    refreshType();
    refreshEmployeeDetails();
});
employeeSelect.addEventListener('change', refreshEmployeeDetails);
methodSelect.addEventListener('change', refreshAccounts);
refreshType();
refreshEmployeeDetails();
refreshAccounts();
</script>
@endsection
