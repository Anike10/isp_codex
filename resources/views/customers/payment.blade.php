@extends('layouts.app')

@section('content')
@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
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
    .advance-choice {
        display:flex;
        align-items:flex-start;
        gap:10px;
        padding:12px 14px;
        border:1px solid var(--line);
        border-radius:8px;
        background:#f8fafc;
        cursor:pointer;
    }
    .advance-choice input {
        width:18px;
        height:18px;
        margin-top:2px;
        accent-color:var(--accent);
    }
    .advance-choice strong {
        display:block;
        margin-bottom:3px;
    }
    .advance-choice.selected {
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
        grid-column:1 / -1;
        border:1px solid var(--line);
        border-radius:8px;
        background:#fbfcfe;
        padding:14px;
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
        .payment-field-row {
            grid-template-columns:1fr;
        }
    }
</style>

<div class="topbar">
    <div>
        <h1>Record Party Payment</h1>
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
        <label class="advance-choice" id="advanceChoice">
            <input type="checkbox" id="keepAsAdvance" name="keep_as_advance" value="1" @checked(old('keep_as_advance') === '1')>
            <span>
                <strong>Keep as advance</strong>
                <span class="muted">Tick only when this payment should not auto-adjust the oldest due invoice. You can still choose invoice amounts below.</span>
            </span>
        </label>
    </div>
    <div class="payment-field-row">
        <div>
            <label>Amount</label>
            <input type="text" inputmode="decimal" autocomplete="off" name="amount" id="amountInput" value="{{ old('amount', $totalDue > 0 ? number_format($totalDue, 2, '.', '') : '') }}" required>
            <div class="muted">The exact current due is filled automatically. This field has no mouse-wheel or arrow-step adjustment.</div>
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
                <h3 id="allocationTitle">Invoice Adjustment From This Entry</h3>
                <div class="muted" id="allocationHelp">Oldest due invoices will be adjusted automatically from this payment.</div>
            </div>
            <span class="badge pending" id="allocationBadge">Auto</span>
        </div>
        <table>
            <thead><tr><th>Invoice</th><th>Due</th><th>Pay From This Entry</th></tr></thead>
            <tbody>
                @forelse ($dueInvoices as $invoice)
                    <tr>
                        <td>
                            @if ($canOpenInvoices)
                                <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                            @else
                                {{ $invoice->invoice_no }}
                            @endif
                            <div class="muted">{{ $invoice->formatted_billing_month }} - {{ $invoice->due_date?->format('Y-m-d') ?? 'No due date' }}</div>
                        </td>
                        <td>{{ number_format($invoice->due_amount, 2) }}</td>
                        <td>
                            <input class="allocation-input" type="number" step="0.01" min="0" max="{{ $invoice->due_amount }}" data-due="{{ $invoice->due_amount }}" name="invoice_allocations[{{ $invoice->id }}]" value="{{ old('invoice_allocations.'.$invoice->id, 0) }}">
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
const keepAsAdvance = document.getElementById('keepAsAdvance');
const advanceChoice = document.getElementById('advanceChoice');
const paymentSubmit = document.getElementById('paymentSubmit');
const methodSelect = document.getElementById('paymentMethod');
const accountWrap = document.getElementById('accountSelectWrap');
const accountSelect = document.getElementById('paymentAccount');
const amountInput = document.getElementById('amountInput');
const afterPayment = document.getElementById('afterPayment');
const paymentPreview = document.getElementById('paymentPreview');
const allocationInputs = Array.from(document.querySelectorAll('.allocation-input'));
const allocationTitle = document.getElementById('allocationTitle');
const allocationHelp = document.getElementById('allocationHelp');
const allocationBadge = document.getElementById('allocationBadge');
let lastSaveAsAdvance = keepAsAdvance.checked;

function money(value) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
}

function refreshPreview() {
    const amount = Number(amountInput.value || 0);
    const saveAsAdvance = keepAsAdvance.checked;

    if (saveAsAdvance !== lastSaveAsAdvance) {
        allocationInputs.forEach(input => {
            input.value = '0';
        });
        lastSaveAsAdvance = saveAsAdvance;
    }

    if (! saveAsAdvance) {
        let remaining = amount;

        allocationInputs.forEach(input => {
            const due = Number(input.dataset.due || 0);
            const applied = Math.min(remaining, due);
            input.value = applied > 0 ? applied.toFixed(2) : '0';
            remaining = Math.max(0, remaining - applied);
        });
    }

    const allocationTotal = allocationInputs.reduce((sum, input) => sum + Number(input.value || 0), 0);
    const available = saveAsAdvance ? advanceBalance : advanceBalance + amount;
    const duePaid = saveAsAdvance ? Math.min(allocationTotal, totalDue) : Math.min(available, totalDue);
    const remainingDue = Math.max(0, totalDue - available);
    const advanceAfterManualAllocation = advanceBalance + amount - allocationTotal;
    const remainingAdvance = saveAsAdvance ? Math.max(0, advanceAfterManualAllocation) : Math.max(0, available - totalDue);
    const dueAfterManualAllocation = Math.max(0, totalDue - allocationTotal);
    const netAfter = saveAsAdvance ? remainingAdvance - dueAfterManualAllocation : remainingAdvance - remainingDue;

    paymentForm.action = saveAsAdvance ? advanceUrl : paymentUrl;
    paymentSubmit.textContent = saveAsAdvance ? 'Save Advance Payment' : 'Save Payment';
    advanceChoice.classList.toggle('selected', saveAsAdvance);
    allocationTitle.textContent = saveAsAdvance ? 'Optional Invoice Adjustment From This Entry' : 'Invoice Adjustment From This Entry';
    allocationHelp.textContent = saveAsAdvance
        ? 'Enter amounts only if part of this advance should pay old invoices now. Leave all amounts 0 to keep the full payment as advance.'
        : 'Oldest due invoices will be adjusted automatically from this payment.';
    allocationBadge.textContent = saveAsAdvance ? 'Optional' : 'Auto';
    allocationInputs.forEach(input => {
        input.disabled = ! saveAsAdvance;
    });
    afterPayment.textContent = money(netAfter);

    if (amount <= 0) {
        paymentPreview.textContent = 'Enter amount to preview due and balance update.';
        return;
    }

    paymentPreview.textContent = saveAsAdvance
        ? `Invoice adjusted: ${money(duePaid)} | Remaining due: ${money(dueAfterManualAllocation)} | Advance balance: ${money(remainingAdvance)}`
        : `Auto-adjust oldest dues: ${money(duePaid)} | Remaining due: ${money(remainingDue)} | Advance balance: ${money(remainingAdvance)}`;
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
keepAsAdvance.addEventListener('change', refreshPreview);
amountInput.addEventListener('input', refreshPreview);
allocationInputs.forEach(input => input.addEventListener('input', refreshPreview));
refreshAccounts();
refreshPreview();
</script>
@endsection
