@extends('layouts.app')

@section('content')
@php
    $accountsByMethod = $paymentAccounts->groupBy('payment_method')->map(fn ($accounts) => $accounts->map(fn ($account) => [
        'id' => $account->id,
        'label' => $account->account_name.' - '.$account->account_number,
    ])->values())->toArray();
    $selectedDuration = old('duration', 'month_1');
    $selectedPaymentMethod = old('payment_method', $paymentDefault['payment_method'] ?? 'cash');
    $selectedPaymentAccountId = old('payment_account_id', $paymentDefault['payment_account_id'] ?? null);
    $hasUnpayableRows = $rows->contains(fn ($row) => ! $row['payable']);
@endphp

<style>
    .bulk-payment-shell { display:grid; gap:16px; max-width:1240px; margin:0 auto; }
    .bulk-payment-hero { display:flex; justify-content:space-between; align-items:center; gap:18px; padding:20px 22px; border-radius:14px; background:linear-gradient(115deg,#12324a,#08745b 58%,#1684c7); color:#fff; }
    .bulk-payment-hero h1 { margin:0 0 5px; color:#fff; }
    .bulk-payment-hero p { margin:0; color:rgba(255,255,255,.78); }
    .bulk-payment-controls { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:13px; }
    .bulk-payment-controls .full { grid-column:1/-1; }
    .bulk-payment-controls label { display:grid; gap:6px; font-size:13px; font-weight:800; }
    .bulk-payment-controls input,.bulk-payment-controls select,.bulk-payment-controls textarea { width:100%; }
    .bulk-payment-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .bulk-payment-stat { padding:14px; border:1px solid #d8dee9; border-radius:10px; background:#f8fafc; }
    .bulk-payment-stat span,.bulk-payment-stat strong { display:block; }
    .bulk-payment-stat span { color:#667085; font-size:12px; }
    .bulk-payment-stat strong { margin-top:4px; font-size:22px; }
    .bulk-payment-table-wrap { overflow:auto; border:1px solid #d8dee9; border-radius:10px; }
    .bulk-payment-table-wrap table { margin:0; }
    .bulk-payment-error { padding:12px 14px; border:1px solid #fecdca; border-radius:8px; background:#fff4f2; color:#b42318; }
    .bulk-payment-actions { display:flex; justify-content:flex-end; gap:10px; position:sticky; bottom:10px; padding:12px; border:1px solid #d8dee9; border-radius:10px; background:rgba(255,255,255,.96); box-shadow:0 12px 30px rgba(15,23,42,.14); }
    @media(max-width:850px){.bulk-payment-controls{grid-template-columns:1fr 1fr}.bulk-payment-summary{grid-template-columns:1fr}.bulk-payment-hero{align-items:flex-start;flex-direction:column}}
</style>

<div class="bulk-payment-shell">
    <header class="bulk-payment-hero">
        <div>
            <h1>Bulk Party Payment</h1>
        <p>Review selected parties, create a paid invoice for each party and complete one payment batch.</p>
        </div>
        <a class="btn light" href="{{ route('customers.index') }}">Back to Parties</a>
    </header>

    @if ($errors->any())
        <div class="bulk-payment-error">
            <strong>Bulk payment could not be completed.</strong>
            <ul style="margin:8px 0 0;padding-left:20px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($hasUnpayableRows)
        <div class="bulk-payment-error">One or more selected parties have no payable assigned package. Go back and remove those parties before completing payment.</div>
    @endif

    <form method="post" action="{{ route('customers.bulk-payments.store', $token) }}" class="bulk-payment-shell" id="bulkPaymentForm">
        @csrf
        <section class="card bulk-payment-controls">
            <label>
                Validity
                <select name="duration" id="bulkDuration">
                    @foreach ($durationOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedDuration === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Payment Date
                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
            </label>
            <label>
                Payment Method
                <select name="payment_method" id="bulkPaymentMethod">
                    <option value="cash" @selected($selectedPaymentMethod === 'cash')>Cash</option>
                    <option value="bkash" @selected($selectedPaymentMethod === 'bkash')>bKash</option>
                    <option value="nagad" @selected($selectedPaymentMethod === 'nagad')>Nagad</option>
                    <option value="bank" @selected($selectedPaymentMethod === 'bank')>Bank</option>
                </select>
            </label>
            <label>
                Payment Account
                <select name="payment_account_id" id="bulkPaymentAccount"><option value="">Select account</option></select>
            </label>
            <label>
                Reference
                <input name="reference" value="{{ old('reference') }}" placeholder="Transaction ID / receipt no">
            </label>
            <label class="full">
                Note
                <textarea name="note" rows="2" placeholder="Optional bulk payment note">{{ old('note') }}</textarea>
            </label>
            <div class="full">@include('partials.payment_default_checkbox')</div>
        </section>

        <section class="bulk-payment-summary">
            <article class="bulk-payment-stat"><span>Selected Parties</span><strong>{{ $rows->count() }}</strong></article>
            <article class="bulk-payment-stat"><span>Selected Validity</span><strong id="bulkDurationLabel">{{ $durationOptions[$selectedDuration] }}</strong></article>
            <article class="bulk-payment-stat"><span>Total Payment</span><strong id="bulkPaymentTotal">0.00</strong></article>
        </section>

        <section class="bulk-payment-table-wrap">
            <table>
                <thead><tr><th>#</th><th>Party</th><th>User ID</th><th>Package</th><th>Monthly Price</th><th>Payment Amount</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr data-bulk-payment-row data-amounts='@json($row['amounts'])'>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row['customer']->name }}</strong><div class="muted">Party #{{ $row['customer']->id }}</div></td>
                            <td>{{ $row['customer']->connection_id ?: $row['customer']->mikrotik_username ?: 'Not provided' }}</td>
                            <td>
                                {{ $row['package']?->name ?? 'No assigned package' }}
                                @unless ($row['payable'])<div class="muted" style="color:#b42318">Cannot process</div>@endunless
                            </td>
                            <td>{{ number_format((float) ($row['package']?->monthly_price ?? 0), 2) }}</td>
                            <td><strong data-bulk-row-amount>0.00</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <div class="bulk-payment-actions">
            <a class="btn light" href="{{ route('customers.index') }}">Cancel</a>
            <button class="btn" type="submit" id="completeBulkPayment" @disabled($hasUnpayableRows)>Complete Bulk Payment</button>
        </div>
    </form>
</div>

<script>
const bulkDurationLabels = @json($durationOptions);
const bulkAccountsByMethod = @json($accountsByMethod);
const bulkOldAccountId = @json($selectedPaymentAccountId);
const bulkDuration = document.getElementById('bulkDuration');
const bulkMethod = document.getElementById('bulkPaymentMethod');
const bulkAccount = document.getElementById('bulkPaymentAccount');

function updateBulkAmounts() {
    const duration = bulkDuration.value;
    let total = 0;
    document.querySelectorAll('[data-bulk-payment-row]').forEach((row) => {
        const amounts = JSON.parse(row.dataset.amounts || '{}');
        const amount = Number(amounts[duration] || 0);
        row.querySelector('[data-bulk-row-amount]').textContent = amount.toFixed(2);
        total += amount;
    });
    document.getElementById('bulkDurationLabel').textContent = bulkDurationLabels[duration] || '';
    document.getElementById('bulkPaymentTotal').textContent = total.toFixed(2);
}

function updateBulkAccounts() {
    const method = bulkMethod.value;
    const accounts = bulkAccountsByMethod[method] || [];
    bulkAccount.replaceChildren(new Option(method === 'cash' ? 'Cash payment - no account needed' : 'Select account', ''));
    accounts.forEach((account) => bulkAccount.add(new Option(account.label, account.id)));
    bulkAccount.disabled = method === 'cash';
    bulkAccount.required = method !== 'cash';
    if (method !== 'cash' && bulkOldAccountId) bulkAccount.value = String(bulkOldAccountId);
}

bulkDuration.addEventListener('change', updateBulkAmounts);
bulkMethod.addEventListener('change', updateBulkAccounts);
document.getElementById('bulkPaymentForm').addEventListener('submit', function () {
    const button = document.getElementById('completeBulkPayment');
    button.disabled = true;
    button.textContent = 'Processing...';
});
updateBulkAmounts();
updateBulkAccounts();
</script>
@endsection
