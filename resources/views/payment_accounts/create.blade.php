@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Add Payment Account</h1>
        <div class="muted">Add bKash, Nagad, or bank account details</div>
    </div>
    <a class="btn light" href="{{ route('payment-accounts.index') }}">Back</a>
</div>

<form method="post" action="{{ route('payment-accounts.store') }}" class="card form-grid">
    @csrf
    <div>
        <label>Payment Method</label>
        <select name="payment_method" required>
            <option value="bkash" @selected(old('payment_method') === 'bkash')>bKash</option>
            <option value="nagad" @selected(old('payment_method') === 'nagad')>Nagad</option>
            <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
        </select>
    </div>
    <div>
        <label>Account Name</label>
        <input name="account_name" value="{{ old('account_name') }}" placeholder="Office bKash, Islami Bank, Main Nagad" required>
    </div>
    <div>
        <label>Account Number</label>
        <input name="account_number" value="{{ old('account_number') }}" required>
    </div>
    <div>
        <label>Opening Balance</label>
        <input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', '0.00') }}" required>
    </div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="full">
        <button class="btn" type="submit">Save Account</button>
    </div>
</form>
@endsection
