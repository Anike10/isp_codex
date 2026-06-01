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
    $totalDue = (float) $dueInvoices->sum('due_amount');
    $advanceBalance = (float) $customer->account_balance;
    $netBalance = $advanceBalance - $totalDue;
@endphp

<style>
    .payment-mode {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:12px;
    }
    .payment-mode-option {
        position:relative;
        display:grid;
        gap:6px;
        min-height:92px;
        padding:14px 14px 14px 44px;
        border:1px solid var(--line);
        border-radius:8px;
        background:#f8fafc;
        cursor:pointer;
    }
    .payment-mode-option input {
        position:absolute;
        left:14px;
        top:18px;
        width:18px;
        height:18px;
        margin:0;
        accent-color:var(--brand);
    }
    .payment-mode-option strong {
        font-size:16px;
        line-height:1.25;
    }
    .payment-mode-option span {
        color:var(--muted);
        font-size:13px;
        line-height:1.4;
    }
    .payment-mode-option.selected {
        border-color:var(--brand);
        background:#eef8f4;
        box-shadow:0 0 0 2px rgba(17, 97, 73, .12);
    }
    .payment-mode-option.advance.selected {
        border-color:var(--accent);
        background:#eef6ff;
        box-shadow:0 0 0 2px rgba(29, 118, 201, .12);
    }
    .payment-summary {
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        padding:10px 12px;
        border:1px solid var(--line);
        border-radius:8px;
        background:#fbfcfe;
    }
    .payment-field-row {
        display:grid;
        grid-column:1 / -1;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:16px;
    }
    .advance-allocation-panel {
        display:none;
        grid-column:1 / -1;
        border:1px solid var(--line);
        border-radius:8px;
        background:#fbfcfe;
        padding:14px;
    }
    .advance-allocation-panel.visible {
        display:block;
    }
    .allocation-heading {
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        margin-bottom:10px;
    }
    .allocation-heading h3 {
        margin:0;
        font-size:18px;
    }
    .allocation-input {
        min-width:140px;
    }
    @media (max-width: 700px) {
        .payment-mode,
        .payment-field-row {
            grid-template-columns:1fr;
        }
    }
</style>

<div class="topbar">
    <div>
        <h1>Record Customer Payment</h1>
        <div class="muted">{{ $customer->name }} - {{ $customer->connection_id }}</div>
    </div>
    <a class="btn light" href="{{ route('customers.show', $customer) }}">Back</a>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat">
        <span class="muted">Total Due</span>
        <strong id="totalDue">{{ number_format($totalDue, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Advance Balance</span>
        <strong id="advanceBalance">{{ number_format($advanceBalance, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Net Balance</span>
        <strong id="netBalance">{{ number_format($netBalance, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">After Payment</span>
        <strong id="afterPayment">{{ number_format($netBalance, 2) }}</strong>
    </div>
</div>

<form method="post" action="{{ route('customers.payments.store', $customer) }}" class="card form-grid" id="paymentForm">
    @csrf
    <div class="full">
        <h2>Payment Entry</h2>
    </div>
    <div class="full">
        <label>Payment Type</label>
        <div class="payment-mode">
            <label class="payment-mode-option" id="dueModeCard">
                <input type="radio" name="payment_mode_choice" value="due" id="paymentModeDue" @checked($totalDue > 0)>
                <strong>Pay Invoice Due</strong>
                <span>Use this received money to clear this customer's unpaid invoice first. Extra money will become advance balance.</span>
            </label>
            <label class="payment-mode-option advance" id="advanceModeCard">
                <input type="radio" name="payment_mode_choice" value="advance" id="paymentModeAdvance" @checked($totalDue <= 0)>
                <strong>Keep As Advance</strong>
                <span>Use this when no invoice should be paid now. The full amount stays in the customer's advance balance.</span>
            </label>
        </div>
    </div>
    <div class="payment-field-row">
        <div>
            <label>Amount</label>
            <input type="number" step="0.01" min="1" name="amount" id="amountInput" value="{{ old('amount') }}" required>
            <div class="payment-summary muted" id="paymentPreview">Enter amount to preview due and balance update.</div>
        </div>
        <div>
            <label>Payment Date</label>
            <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
        </div>
    </div>
    <div class="payment-field-row">
        <div>
            <label>Method</label>
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
    </div>
    <div class="advance-allocation-panel" id="advanceAllocationPanel">
        <div class="allocation-heading">
            <div>
                <h3>Invoice Adjustment From This Entry</h3>
                <div class="muted">Put an amount beside any due invoice you want to pay now. Leave all amounts 0 to keep the full payment as advance.</div>
            </div>
            <span class="badge pending">Optional</span>
        </div>
        <table>
            <thead><tr><th>Invoice</th><th>Due</th><th>Pay From This Entry</th></tr></thead>
            <tbody>
                @forelse ($dueInvoices as $invoice)
                    <tr>
                        <td>
                            <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                            <div class="muted">{{ $invoice->formatted_billing_month }} - {{ $invoice->due_date?->format('Y-m-d') ?? 'No due date' }}</div>
                        </td>
                        <td>{{ number_format($invoice->due_amount, 2) }}</td>
                        <td>
                            <input class="allocation-input" type="number" step="0.01" min="0" max="{{ $invoice->due_amount }}" name="invoice_allocations[{{ $invoice->id }}]" value="{{ old('invoice_allocations.'.$invoice->id, 0) }}">
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">No due invoice found. The full amount will stay as advance.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="full">
        <label>Reference</label>
        <input name="reference" value="{{ old('reference') }}" placeholder="TrxID, receipt no, or note reference">
    </div>
    <div class="full">
        <label>Note</label>
        <textarea name="note">{{ old('note') }}</textarea>
    </div>
    <div class="full">
        <button class="btn" type="submit" id="paymentSubmit">Save Payment</button>
    </div>
</form>

<section class="card" style="margin-top:16px">
    <h2>Advance Balance History</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th><th>Reference</th><th>Note</th></tr></thead>
        <tbody>
            @forelse ($balanceTransactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_date?->format('Y-m-d') }}</td>
                    <td><span class="badge {{ $transaction->direction === 'credit' ? 'active' : 'due' }}">{{ $transaction->direction }}</span></td>
                    <td>{{ number_format($transaction->amount, 2) }}</td>
                    <td>{{ number_format($transaction->balance_after, 2) }}</td>
                    <td>{{ $transaction->reference ?? 'N/A' }}</td>
                    <td>{{ $transaction->note ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No advance balance history yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<script>
const accountsByMethod = @json($accountsByMethod);
const oldAccountId = @json(old('payment_account_id'));
const totalDue = Number(@json($totalDue));
const advanceBalance = Number(@json($advanceBalance));
const paymentUrl = @json(route('customers.payments.store', $customer));
const advanceUrl = @json(route('customers.advance-payments.store', $customer));
const paymentForm = document.getElementById('paymentForm');
const paymentModeDue = document.getElementById('paymentModeDue');
const paymentModeAdvance = document.getElementById('paymentModeAdvance');
const dueModeCard = document.getElementById('dueModeCard');
const advanceModeCard = document.getElementById('advanceModeCard');
const paymentSubmit = document.getElementById('paymentSubmit');
const methodSelect = document.getElementById('paymentMethod');
const accountWrap = document.getElementById('accountSelectWrap');
const accountSelect = document.getElementById('paymentAccount');
const amountInput = document.getElementById('amountInput');
const afterPayment = document.getElementById('afterPayment');
const paymentPreview = document.getElementById('paymentPreview');
const advanceAllocationPanel = document.getElementById('advanceAllocationPanel');
const allocationInputs = Array.from(document.querySelectorAll('.allocation-input'));

function money(value) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
}

function refreshPreview() {
    const amount = Number(amountInput.value || 0);
    const payDue = paymentModeDue.checked;
    const allocationTotal = allocationInputs.reduce((sum, input) => sum + Number(input.value || 0), 0);
    const available = payDue ? advanceBalance + amount : advanceBalance;
    const duePaid = payDue ? Math.min(available, totalDue) : Math.min(allocationTotal, totalDue);
    const remainingDue = Math.max(0, totalDue - available);
    const advanceAfterManualAllocation = advanceBalance + amount - allocationTotal;
    const remainingAdvance = payDue ? Math.max(0, available - totalDue) : Math.max(0, advanceAfterManualAllocation);
    const dueAfterManualAllocation = Math.max(0, totalDue - allocationTotal);
    const netAfter = payDue ? remainingAdvance - remainingDue : remainingAdvance - dueAfterManualAllocation;

    paymentForm.action = payDue ? paymentUrl : advanceUrl;
    paymentSubmit.textContent = payDue ? 'Save Payment' : 'Save Advance Payment';
    dueModeCard.classList.toggle('selected', payDue);
    advanceModeCard.classList.toggle('selected', ! payDue);
    advanceAllocationPanel.classList.toggle('visible', ! payDue);
    allocationInputs.forEach(input => {
        input.disabled = payDue;
    });
    afterPayment.textContent = money(netAfter);

    if (amount <= 0) {
        paymentPreview.textContent = 'Enter amount to preview due and balance update.';
        return;
    }

    paymentPreview.textContent = payDue
        ? `Due paid: ${money(duePaid)} | Remaining due: ${money(remainingDue)} | Advance balance: ${money(remainingAdvance)} | Line: ${remainingDue <= 0 ? 'can be active' : 'still due'}`
        : `Invoice adjusted: ${money(duePaid)} | Remaining due: ${money(dueAfterManualAllocation)} | Advance balance: ${money(remainingAdvance)}`;
}

function refreshAccounts() {
    const method = methodSelect.value;
    const needsAccount = method !== 'cash';

    accountWrap.style.display = needsAccount ? 'block' : 'none';
    accountSelect.required = needsAccount;
    accountSelect.innerHTML = '<option value="">Select account</option>';

    if (! needsAccount) {
        accountSelect.value = '';
        return;
    }

    (accountsByMethod[method] || []).forEach(account => {
        const option = document.createElement('option');
        option.value = account.id;
        option.textContent = account.label;
        option.selected = String(oldAccountId) === String(account.id);
        accountSelect.appendChild(option);
    });
}

methodSelect.addEventListener('change', refreshAccounts);
paymentModeDue.addEventListener('change', refreshPreview);
paymentModeAdvance.addEventListener('change', refreshPreview);
amountInput.addEventListener('input', refreshPreview);
allocationInputs.forEach(input => input.addEventListener('input', refreshPreview));
refreshAccounts();
refreshPreview();
</script>
@endsection
