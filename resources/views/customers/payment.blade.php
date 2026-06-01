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
    @media (max-width: 700px) {
        .payment-mode {
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
    <div>
        <label>Amount</label>
        <input type="number" step="0.01" min="1" name="amount" id="amountInput" value="{{ old('amount') }}" required>
        <div class="payment-summary muted" id="paymentPreview">Enter amount to preview due and balance update.</div>
    </div>
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
    <div>
        <label>Payment Date</label>
        <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
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
    <h2>Apply Existing Advance To Invoice</h2>
        <table>
            <thead><tr><th>Invoice</th><th>Due</th><th>Apply</th></tr></thead>
            <tbody>
                @forelse ($dueInvoices as $invoice)
                    @php
                        $maxApply = min($advanceBalance, (float) $invoice->due_amount);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                            <div class="muted">{{ $invoice->formatted_billing_month }} - {{ $invoice->due_date?->format('Y-m-d') ?? 'No due date' }}</div>
                        </td>
                        <td>{{ number_format($invoice->due_amount, 2) }}</td>
                        <td>
                            @if ($maxApply > 0)
                                <form method="post" action="{{ route('customers.advance-payments.apply', $customer) }}" class="actions">
                                    @csrf
                                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                                    <input type="hidden" name="payment_date" value="{{ now()->toDateString() }}">
                                    <input type="number" step="0.01" min="1" max="{{ $maxApply }}" name="amount" value="{{ number_format($maxApply, 2, '.', '') }}" required>
                                    <button class="btn secondary" type="submit">Apply</button>
                                </form>
                            @else
                                <span class="muted">No balance</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">No due invoice found.</td></tr>
                @endforelse
            </tbody>
        </table>
</section>

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

function money(value) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
}

function refreshPreview() {
    const amount = Number(amountInput.value || 0);
    const payDue = paymentModeDue.checked;
    const available = payDue ? advanceBalance + amount : advanceBalance;
    const duePaid = Math.min(available, totalDue);
    const remainingDue = Math.max(0, totalDue - available);
    const remainingAdvance = payDue ? Math.max(0, available - totalDue) : advanceBalance + amount;
    const netAfter = remainingAdvance - remainingDue;

    paymentForm.action = payDue ? paymentUrl : advanceUrl;
    paymentSubmit.textContent = payDue ? 'Save Payment' : 'Save As Advance';
    dueModeCard.classList.toggle('selected', payDue);
    advanceModeCard.classList.toggle('selected', ! payDue);
    afterPayment.textContent = money(netAfter);

    if (amount <= 0) {
        paymentPreview.textContent = 'Enter amount to preview due and balance update.';
        return;
    }

    paymentPreview.textContent = payDue
        ? `Due paid: ${money(duePaid)} | Remaining due: ${money(remainingDue)} | Advance balance: ${money(remainingAdvance)} | Line: ${remainingDue <= 0 ? 'can be active' : 'still due'}`
        : `Advance balance after entry: ${money(remainingAdvance)} | Existing due stays: ${money(totalDue)}`;
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
refreshAccounts();
refreshPreview();
</script>
@endsection
