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
    $advanceBalance = (float) $customer->account_balance;
    $dueInvoices = $customer->invoices;
@endphp

<div class="topbar">
    <div>
        <h1>Advance Payment</h1>
        <div class="muted">{{ $customer->name }} - {{ $customer->connection_id }}</div>
    </div>
    <a class="btn light" href="{{ route('customers.show', $customer) }}">Back</a>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat">
        <span class="muted">Advance Balance</span>
        <strong>{{ number_format($advanceBalance, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Due Invoices</span>
        <strong>{{ $dueInvoices->count() }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Total Due</span>
        <strong>{{ number_format($dueInvoices->sum('due_amount'), 2) }}</strong>
    </div>
</div>

<div class="grid two">
    <form method="post" action="{{ route('customers.advance-payments.store', $customer) }}" class="card form-grid">
        @csrf
        <div class="full">
            <h2>Add Advance Balance</h2>
        </div>
        <div>
            <label>Amount</label>
            <input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" required>
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
            <button class="btn" type="submit">Save Advance Payment</button>
        </div>
    </form>

    <section class="card">
        <h2>Apply Advance To Invoice</h2>
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
</div>

<section class="card" style="margin-top:16px">
    <h2>Advance Balance History</h2>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th><th>Reference</th><th>Note</th></tr></thead>
        <tbody>
            @forelse ($customer->balanceTransactions as $transaction)
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
const methodSelect = document.getElementById('paymentMethod');
const accountWrap = document.getElementById('accountSelectWrap');
const accountSelect = document.getElementById('paymentAccount');

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
refreshAccounts();
</script>
@endsection
