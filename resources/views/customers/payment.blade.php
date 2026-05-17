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

<form method="post" action="{{ route('customers.payments.store', $customer) }}" class="card form-grid">
    @csrf
    <div>
        <label>Amount</label>
        <input type="number" step="0.01" min="1" name="amount" id="amountInput" value="{{ old('amount') }}" required>
        <span class="muted" id="paymentPreview">Enter amount to preview due and balance update.</span>
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
        <label>Note</label>
        <textarea name="note">{{ old('note') }}</textarea>
    </div>
    <div class="full">
        <button class="btn" type="submit">Save Payment</button>
    </div>
</form>

<section class="card" style="margin-top:16px">
    <h2>Due Invoices</h2>
    <table>
        <thead><tr><th>Invoice</th><th>Month</th><th>Due Date</th><th>Due</th></tr></thead>
        <tbody>
            @forelse ($dueInvoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a></td>
                    <td>{{ $invoice->formatted_billing_month }}</td>
                    <td>{{ $invoice->due_date?->format('Y-m-d') ?? 'N/A' }}</td>
                    <td>{{ number_format($invoice->due_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No due invoices. Payment will stay as advance balance if a bill cannot be generated.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<script>
const accountsByMethod = @json($accountsByMethod);
const oldAccountId = @json(old('payment_account_id'));
const totalDue = Number(@json($totalDue));
const advanceBalance = Number(@json($advanceBalance));
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
    const available = advanceBalance + amount;
    const duePaid = Math.min(available, totalDue);
    const remainingDue = Math.max(0, totalDue - available);
    const remainingAdvance = Math.max(0, available - totalDue);
    const netAfter = remainingAdvance - remainingDue;

    afterPayment.textContent = money(netAfter);

    if (amount <= 0) {
        paymentPreview.textContent = 'Enter amount to preview due and balance update.';
        return;
    }

    paymentPreview.textContent = `Due paid: ${money(duePaid)} | Remaining due: ${money(remainingDue)} | Advance balance: ${money(remainingAdvance)} | Line: ${remainingDue <= 0 ? 'can be active' : 'still due'}`;
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
amountInput.addEventListener('input', refreshPreview);
refreshAccounts();
refreshPreview();
</script>
@endsection
