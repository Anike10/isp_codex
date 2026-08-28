@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Deposit to Office</h1>
        <div class="muted">{{ $account->account_name }} &middot; {{ $account->account_number }}</div>
    </div>
    <a class="btn light" href="{{ route('payment-accounts.show', $account) }}">Back to ledger</a>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Current balance</span><strong>{{ number_format($liveBalance, 2) }}</strong></div>
    <div class="card stat">
        <span class="muted">Balance limit</span>
        <strong>{{ $account->balance_limit === null ? 'None' : number_format((float) $account->balance_limit, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Room left before limit</span>
        <strong>{{ $account->remainingCapacity() === null ? 'Unlimited' : number_format($account->remainingCapacity(), 2) }}</strong>
    </div>
</div>

<form method="post" action="{{ route('account-deposits.store', $account) }}" class="card form-grid">
    @csrf
    <div>
        <label>Amount handed to office</label>
        <input type="number" step="0.01" min="0.01" max="{{ number_format($liveBalance, 2, '.', '') }}" name="amount" value="{{ old('amount') }}" required>
        <span class="muted">Cannot be more than the current balance.</span>
    </div>
    <div>
        <label>Deposit date</label>
        <input type="date" name="deposited_at" value="{{ old('deposited_at', now()->toDateString()) }}" required>
    </div>
    <div><label>Reference (optional)</label><input name="reference" value="{{ old('reference') }}" placeholder="Slip no, received by, etc."></div>
    <div class="full"><label>Note (optional)</label><textarea name="note" rows="2">{{ old('note') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Record Deposit</button></div>
</form>
@endsection
