@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Record Payment</h1><div class="muted">Collect against an invoice or deposit directly to any party ledger</div></div>
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
    $selectedInvoiceId = old('invoice_id', request('invoice_id'));
    $selectedCustomerId = old('customer_id', request('customer_id'));
    $partyOptions = $customers->map(fn ($customer) => [
        'id' => $customer->id,
        'name' => $customer->name,
        'connection_id' => $customer->connection_id,
        'phone' => $customer->phone,
        'balance' => number_format($customer->account_balance, 2),
    ])->values();
    $unpaidInvoicesByParty = $invoices
        ->groupBy('customer_id')
        ->map(fn ($partyInvoices) => $partyInvoices->map(fn ($invoice) => [
            'id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
            'billing_month' => $invoice->formatted_billing_month,
            'due_amount' => number_format($invoice->due_amount, 2),
        ])->values());
@endphp

<form method="post" action="{{ route('payments.store') }}" class="card form-grid">
    @csrf
    <div class="full" style="position:relative">
        <label>Party</label>
        <input id="partySearch" placeholder="Search by party name, connection ID, or phone" autocomplete="off">
        <input type="hidden" name="customer_id" id="partyId" value="{{ $selectedCustomerId }}">
        <div id="partySuggestions" class="suggestion-list"></div>
    </div>
    <div class="full">
        <label>Unpaid Invoice (Optional)</label>
        <select name="invoice_id" id="invoiceTarget" disabled>
            <option value="">Party Ledger (Advance) - no invoice selected</option>
        </select>
        <span class="muted" id="invoiceHelp">Select a party to load its unpaid invoices. Leave this on Party Ledger to save the amount as advance.</span>
    </div>
    <div>
        <label>Amount</label>
        <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required>
        <span class="muted" id="amountHelp">If amount is more than due, extra money will stay in the party account.</span>
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
    <div id="referenceWrap"><label>Reference / Transaction ID</label><input name="reference" id="paymentReference" value="{{ old('reference') }}"></div>
    <div class="full"><label>Note</label><textarea name="note">{{ old('note') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Save Payment</button></div>
</form>

<script>
const accountsByMethod = @json($accountsByMethod);
const oldAccountId = @json($selectedPaymentAccountId);
const parties = @json($partyOptions);
const unpaidInvoicesByParty = @json($unpaidInvoicesByParty);
const initialPartyId = @json((string) $selectedCustomerId);
const initialInvoiceId = @json((string) $selectedInvoiceId);
const partySearch = document.getElementById('partySearch');
const partyId = document.getElementById('partyId');
const partySuggestions = document.getElementById('partySuggestions');
const invoiceTarget = document.getElementById('invoiceTarget');
const invoiceHelp = document.getElementById('invoiceHelp');
const referenceWrap = document.getElementById('referenceWrap');
const paymentReference = document.getElementById('paymentReference');
const amountHelp = document.getElementById('amountHelp');
const methodSelect = document.getElementById('paymentMethod');
const accountWrap = document.getElementById('accountSelectWrap');
const accountSelect = document.getElementById('paymentAccount');
const newAccountNameWrap = document.getElementById('newAccountNameWrap');
const newAccountNumberWrap = document.getElementById('newAccountNumberWrap');
const newAccountName = document.getElementById('newAccountName');
const newAccountNumber = document.getElementById('newAccountNumber');

function partyLabel(party) {
    return [party.name, party.connection_id, party.phone]
        .filter(Boolean)
        .join(' - ') + ` - Balance ${party.balance}`;
}

function refreshPaymentDestination(preferredInvoiceId = '') {
    const invoices = unpaidInvoicesByParty[String(partyId.value)] || [];
    invoiceTarget.innerHTML = '<option value="">Party Ledger (Advance) - no invoice selected</option>';

    invoices.forEach(invoice => {
        const option = document.createElement('option');
        option.value = invoice.id;
        option.textContent = `${invoice.invoice_no}${invoice.billing_month ? ` - ${invoice.billing_month}` : ''} - Due ${invoice.due_amount}`;
        option.selected = String(preferredInvoiceId) === String(invoice.id);
        invoiceTarget.appendChild(option);
    });

    invoiceTarget.disabled = !partyId.value;
    invoiceHelp.textContent = partyId.value
        ? (invoices.length
            ? 'Choose an invoice to settle it, or keep Party Ledger selected to save the full amount as advance.'
            : 'This party has no unpaid invoice. The payment will be saved to Party Ledger as advance.')
        : 'Select a party to load its unpaid invoices. Leave this on Party Ledger to save the amount as advance.';

    refreshLedgerFields();
}

function selectParty(party, preferredInvoiceId = '') {
    partyId.value = party.id;
    partySearch.value = partyLabel(party);
    partySuggestions.innerHTML = '';
    refreshPaymentDestination(preferredInvoiceId);
}

function refreshPartySuggestions() {
    const query = partySearch.value.trim().toLowerCase();
    const selectedParty = parties.find(party => String(party.id) === String(partyId.value));
    const selectionIsUnchanged = selectedParty && partySearch.value === partyLabel(selectedParty);

    if (!selectionIsUnchanged) {
        partyId.value = '';
        refreshPaymentDestination();
    }

    partySuggestions.innerHTML = '';

    if (!query) {
        return;
    }

    parties
        .filter(party => partyLabel(party).toLowerCase().includes(query))
        .slice(0, 30)
        .forEach(party => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = partyLabel(party);
            button.addEventListener('click', () => selectParty(party));
            partySuggestions.appendChild(button);
        });
}

function refreshLedgerFields() {
    const isLedgerDeposit = !invoiceTarget.value;
    referenceWrap.style.display = isLedgerDeposit ? 'block' : 'none';
    paymentReference.disabled = !isLedgerDeposit;
    amountHelp.textContent = isLedgerDeposit
        ? 'The full amount will be added to the selected party ledger as advance balance.'
        : 'If amount is more than due, extra money will stay in the party account.';
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
partySearch.addEventListener('input', refreshPartySuggestions);
partySearch.addEventListener('focus', refreshPartySuggestions);
invoiceTarget.addEventListener('change', refreshLedgerFields);

const initialParty = parties.find(party => String(party.id) === initialPartyId);
if (initialParty) {
    selectParty(initialParty, initialInvoiceId);
} else {
    refreshPaymentDestination();
}

refreshAccounts();
</script>
@endsection
