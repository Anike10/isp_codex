@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Record Party Payment</h1><div class="muted">{{ $customer->name }} · {{ $customer->connection_id ?? $customer->phone }}</div></div>
    <a class="btn light" href="{{ route('reseller.dashboard') }}">Back</a>
</div>

<div class="grid two" style="margin-bottom:16px">
    <section class="card">
        <h2>Package & Commission</h2>
        <p><strong>Package:</strong> {{ $customer->activeSubscription?->package?->name ?? 'Not assigned' }}</p>
        <p><strong>Full package price:</strong> &#2547; {{ number_format($grossPackagePrice, 2) }}</p>
        <p><strong>Reseller commission:</strong> {{ number_format((float) $reseller->reseller_commission_percent, 2) }}% (&#2547; {{ number_format($commissionAmount, 2) }})</p>
        <p><strong>Current due:</strong> &#2547; {{ number_format($dueTotal, 2) }}</p>
    </section>
    <section class="card">
        <h2>How it works</h2>
        <p class="muted">If the current service invoice does not exist, saving this payment creates it automatically. The reseller commission percentage is saved on that invoice and deducted from its payable amount.</p>
    </section>
</div>

<form method="post" action="{{ route('reseller.customers.payments.store', $customer) }}" class="card form-grid">
    @csrf
    <div><label>Amount</label><input type="text" inputmode="decimal" name="amount" value="{{ old('amount', number_format($suggestedAmount, 2, '.', '')) }}" required></div>
    <div><label>Payment Date</label><input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required></div>
    <div><label>Method</label><select id="resellerPaymentMethod" name="payment_method"><option value="cash">Cash</option><option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="bank">Bank</option></select></div>
    <div id="resellerAccountWrap" style="display:none"><label>Account</label><select id="resellerPaymentAccount" name="payment_account_id"><option value="">Select account</option>@foreach($paymentAccounts as $account)<option value="{{ $account->id }}" data-method="{{ $account->payment_method }}">{{ $account->account_name }} — {{ $account->account_number }}</option>@endforeach</select></div>
    <div class="full"><label>Note</label><textarea name="note">{{ old('note') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Save Payment</button></div>
</form>
<script>
const method = document.getElementById('resellerPaymentMethod');
const account = document.getElementById('resellerPaymentAccount');
const wrap = document.getElementById('resellerAccountWrap');
const options = Array.from(account.options).slice(1);
function refreshResellerAccounts() {
    const needsAccount = method.value !== 'cash';
    wrap.style.display = needsAccount ? 'block' : 'none';
    account.required = needsAccount;
    options.forEach(option => option.hidden = option.dataset.method !== method.value);
    if (!needsAccount || account.selectedOptions[0]?.hidden) account.value = '';
}
method.addEventListener('change', refreshResellerAccounts);
refreshResellerAccounts();
</script>
@endsection
