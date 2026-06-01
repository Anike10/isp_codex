@extends('layouts.app')

@section('content')
@php
    $canRecordPayment = auth()->user()?->hasPermission('manage_payments') && (float) $invoice->due_amount > 0;
    $accountsByMethod = ($paymentAccounts ?? collect())
        ->groupBy('payment_method')
        ->map(fn ($accounts) => $accounts->map(fn ($account) => [
            'id' => $account->id,
            'label' => $account->account_name.' - '.$account->account_number,
        ])->values())
        ->toArray();
@endphp

<div class="topbar">
    <div>
        <h1>{{ $invoice->invoice_no }}</h1>
        <div class="muted">{{ $invoice->customer->name }} - {{ $invoice->formatted_billing_month }}</div>
        <div style="margin-top:8px">
            @if ($invoice->isFinalized())
                <span class="badge active">Final</span>
            @else
                <span class="badge due">Draft</span>
            @endif
        </div>
    </div>
    <div class="actions">
        @if (! $invoice->isFinalized())
            <a class="btn secondary" href="{{ route('invoices.edit', $invoice) }}">Edit</a>
            @if (auth()->user()?->hasPermission('finalize_invoices'))
                <form method="post" action="{{ route('invoices.finalize', $invoice) }}" onsubmit="return confirm('Finalize this invoice? You will not be able to edit it after finalizing.');">
                    @csrf
                    <button class="btn" type="submit">Final</button>
                </form>
            @endif
        @endif
        <a class="btn light" href="{{ route('invoices.challan', $invoice) }}" target="_blank">Print Bill</a>
        <a class="btn light" href="{{ route('invoices.quotation', $invoice) }}" target="_blank">Print Quotation</a>
        <a class="btn light" href="{{ route('invoices.delivery-challan', $invoice) }}" target="_blank">Print Challan</a>
        <form method="post" action="{{ route('invoices.copy-next-month', $invoice) }}" onsubmit="return confirm('Copy this invoice for next month with same items and prices?');">
            @csrf
            <button class="btn light" type="submit">Copy for Next Month</button>
        </form>
        <a class="btn light" href="{{ route('invoices.index') }}">Back</a>
    </div>
</div>

<div class="grid two">
    <section class="card">
        <h2>Invoice</h2>
        <p><strong>Subtotal:</strong> {{ number_format($invoice->subtotal, 2) }}</p>
        @if ((float) $invoice->discount > 0)
            <p><strong>Discount:</strong> {{ number_format($invoice->discount, 2) }}</p>
        @endif
        <p><strong>VAT:</strong> {{ number_format($invoice->vat ?? 0, 2) }}</p>
        <p><strong>Total:</strong> {{ number_format($invoice->total, 2) }}</p>
        <p><strong>Paid:</strong> {{ number_format($invoice->paid_amount, 2) }}</p>
        <p><strong>Due:</strong> {{ number_format($invoice->due_amount, 2) }}</p>
        <p><strong>Status:</strong> <span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></p>
        <p><strong>Finalized:</strong> {{ $invoice->finalized_at?->format('Y-m-d H:i') ?? 'Not finalized' }}</p>
    </section>
    <section class="card">
        <h2>Customer</h2>
        <p><strong>Name:</strong> {{ $invoice->customer->name }}</p>
        <p><strong>Phone:</strong> {{ $invoice->customer->phone }}</p>
        <p><strong>Connection:</strong> {{ $invoice->customer->connection_id }}</p>
        <p><strong>Account Balance:</strong> {{ number_format($invoice->customer->account_balance, 2) }}</p>
        <p><strong>Address:</strong> {{ $invoice->customer->address }}</p>
    </section>
</div>

@if ($canRecordPayment)
<section class="card" style="margin-top:16px">
    <h2>Record Payment</h2>
    <form method="post" action="{{ route('payments.store') }}" class="form-grid" onsubmit="return confirm('Record this payment for {{ $invoice->invoice_no }}?');">
        @csrf
        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
        <input type="hidden" name="redirect_to" value="invoice">

        <div class="full">
            <label>Invoice</label>
            <input value="{{ $invoice->invoice_no }} - {{ $invoice->customer->name }} - Due {{ number_format($invoice->due_amount, 2) }}" readonly>
        </div>
        <div>
            <label>Amount</label>
            <input type="number" step="0.01" min="1" name="amount" value="{{ old('amount', number_format((float) $invoice->due_amount, 2, '.', '')) }}" required>
            <span class="muted">Due is {{ number_format($invoice->due_amount, 2) }}. Extra amount will stay in customer advance balance.</span>
        </div>
        <div>
            <label>Method</label>
            <select name="payment_method" id="invoicePaymentMethod" required>
                <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                <option value="bkash" @selected(old('payment_method') === 'bkash')>bKash</option>
                <option value="nagad" @selected(old('payment_method') === 'nagad')>Nagad</option>
                <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
            </select>
        </div>
        <div id="invoiceAccountSelectWrap">
            <label>Receive To Account</label>
            <select name="payment_account_id" id="invoicePaymentAccount">
                <option value="">Select account</option>
            </select>
            <span class="muted">The payment will be posted to the selected account.</span>
        </div>
        <div id="invoiceCashAccountWrap">
            <label>Receive To Account</label>
            <input value="Cash Ledger" readonly>
            <span class="muted">Cash payments are posted to the cash ledger.</span>
        </div>
        <div id="invoiceNewAccountNameWrap">
            <label>New Account Name</label>
            <input name="new_account_name" id="invoiceNewAccountName" value="{{ old('new_account_name') }}" placeholder="Personal, Office, Bank branch">
        </div>
        <div id="invoiceNewAccountNumberWrap">
            <label>New Account Number</label>
            <input name="new_account_number" id="invoiceNewAccountNumber" value="{{ old('new_account_number') }}" placeholder="Account or mobile number">
        </div>
        <div>
            <label>Payment Date</label>
            <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
        </div>
        <div class="full">
            <label>Note</label>
            <textarea name="note" placeholder="Transaction ID, receiver number, bank note, or any payment detail">{{ old('note', 'Payment received for '.$invoice->invoice_no.'.') }}</textarea>
        </div>
        <div class="full">
            <button class="btn" type="submit">Save Payment</button>
        </div>
    </form>
</section>
@endif

@if($invoice->items->count() > 0)
<section class="card" style="margin-top:16px">
    <h2>Items</h2>
    <table>
        <thead><tr><th style="width:56px;">SL</th><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
        @foreach ($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>
@endif

<section class="card" style="margin-top:16px">
    <h2>Payment Allocations</h2>
    <table>
        <thead><tr><th>Date</th><th>Amount</th><th>Source</th><th>Account</th><th>Note</th></tr></thead>
        <tbody>
        @forelse ($invoice->allocations as $allocation)
            <tr>
                <td>{{ $allocation->allocated_at->format('Y-m-d') }}</td>
                <td>{{ number_format($allocation->amount, 2) }}</td>
                <td>{{ $allocation->source_type === 'advance' ? 'Advance Balance' : 'Payment #'.$allocation->payment_id }}</td>
                <td>{{ $allocation->payment?->account ? $allocation->payment->account->account_name.' - '.$allocation->payment->account->account_number : ($allocation->payment?->payment_method === 'cash' ? 'Cash Ledger' : 'N/A') }}</td>
                <td>{{ $allocation->note }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No allocations yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@if ($canRecordPayment)
<script>
const invoiceAccountsByMethod = @json($accountsByMethod);
const invoiceOldAccountId = @json(old('payment_account_id'));
const invoiceMethodSelect = document.getElementById('invoicePaymentMethod');
const invoiceAccountWrap = document.getElementById('invoiceAccountSelectWrap');
const invoiceCashAccountWrap = document.getElementById('invoiceCashAccountWrap');
const invoiceAccountSelect = document.getElementById('invoicePaymentAccount');
const invoiceNewAccountNameWrap = document.getElementById('invoiceNewAccountNameWrap');
const invoiceNewAccountNumberWrap = document.getElementById('invoiceNewAccountNumberWrap');
const invoiceNewAccountName = document.getElementById('invoiceNewAccountName');
const invoiceNewAccountNumber = document.getElementById('invoiceNewAccountNumber');

function refreshInvoiceAccounts() {
    const method = invoiceMethodSelect.value;
    const needsAccount = method !== 'cash';

    invoiceAccountWrap.style.display = needsAccount ? 'block' : 'none';
    invoiceCashAccountWrap.style.display = needsAccount ? 'none' : 'block';
    invoiceAccountSelect.required = needsAccount;
    invoiceAccountSelect.innerHTML = '<option value="">Select account</option>';

    if (needsAccount) {
        (invoiceAccountsByMethod[method] || []).forEach(account => {
            const option = document.createElement('option');
            option.value = account.id;
            option.textContent = account.label;
            option.selected = String(invoiceOldAccountId) === String(account.id);
            invoiceAccountSelect.appendChild(option);
        });

        const addOption = document.createElement('option');
        addOption.value = '__new__';
        addOption.textContent = '+ Add new account';
        addOption.selected = invoiceOldAccountId === '__new__';
        invoiceAccountSelect.appendChild(addOption);
    } else {
        invoiceAccountSelect.required = false;
        invoiceAccountSelect.value = '';
    }

    refreshInvoiceNewAccountFields();
}

function refreshInvoiceNewAccountFields() {
    const addingNew = invoiceAccountSelect.value === '__new__' && invoiceMethodSelect.value !== 'cash';

    invoiceNewAccountNameWrap.style.display = addingNew ? 'block' : 'none';
    invoiceNewAccountNumberWrap.style.display = addingNew ? 'block' : 'none';
    invoiceNewAccountName.required = addingNew;
    invoiceNewAccountNumber.required = addingNew;
}

invoiceMethodSelect.addEventListener('change', refreshInvoiceAccounts);
invoiceAccountSelect.addEventListener('change', refreshInvoiceNewAccountFields);
refreshInvoiceAccounts();
</script>
@endif
@endsection
