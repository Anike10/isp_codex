@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Record Payment</h1><div class="muted">Collect payment against an unpaid or partial invoice</div></div>
    <a class="btn light" href="{{ route('payments.index') }}">Back</a>
</div>

@php
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

<form method="post" action="{{ route('payments.store') }}" class="card form-grid">
    @csrf
    <div class="full">
        <label>Invoice</label>
        <select name="invoice_id" required>
            <option value="">Select invoice</option>
            @foreach ($invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected((string) old('invoice_id') === (string) $invoice->id)>
                    {{ $invoice->invoice_no }} - {{ $invoice->customer->name }} - Due {{ number_format($invoice->due_amount, 2) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Amount</label>
        <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required>
        <span class="muted">If amount is more than due, extra money will stay in the party account.</span>
    </div>
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <label>Method</label>
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
    <div id="newAccountNameWrap">
        <label>New Account Name</label>
        <input name="new_account_name" id="newAccountName" value="{{ old('new_account_name') }}" placeholder="Personal, Office, Bank branch">
    </div>
    <div id="newAccountNumberWrap">
        <label>New Account Number</label>
        <input name="new_account_number" id="newAccountNumber" value="{{ old('new_account_number') }}" placeholder="Account or mobile number">
    </div>
    <div><label>Payment Date</label><input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required></div>
    <div class="full"><label>Note</label><textarea name="note">{{ old('note') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Save Payment</button></div>
</form>

<script>
const accountsByMethod = @json($accountsByMethod);
const oldAccountId = @json($selectedPaymentAccountId);
const methodSelect = document.getElementById('paymentMethod');
const accountWrap = document.getElementById('accountSelectWrap');
const accountSelect = document.getElementById('paymentAccount');
const newAccountNameWrap = document.getElementById('newAccountNameWrap');
const newAccountNumberWrap = document.getElementById('newAccountNumberWrap');
const newAccountName = document.getElementById('newAccountName');
const newAccountNumber = document.getElementById('newAccountNumber');

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

        const addOption = document.createElement('option');
        addOption.value = '__new__';
        addOption.textContent = '+ Add new account';
        addOption.selected = oldAccountId === '__new__';
        accountSelect.appendChild(addOption);
    } else {
        accountSelect.required = false;
        accountSelect.value = '';
    }

    refreshNewAccountFields();
}

function refreshNewAccountFields() {
    const addingNew = accountSelect.value === '__new__' && methodSelect.value !== 'cash';

    newAccountNameWrap.style.display = addingNew ? 'block' : 'none';
    newAccountNumberWrap.style.display = addingNew ? 'block' : 'none';
    newAccountName.required = addingNew;
    newAccountNumber.required = addingNew;
}

methodSelect.addEventListener('change', refreshAccounts);
accountSelect.addEventListener('change', refreshNewAccountFields);
refreshAccounts();
</script>
@endsection
