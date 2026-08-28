@extends('layouts.app')

@section('content')
@php($editing = $paymentAccount->exists)
<div class="topbar">
    <div>
        <h1>{{ $editing ? 'Edit Payment Account' : 'Add Payment Account' }}</h1>
        <div class="muted">{{ $editing ? 'Update account details or deactivate it' : 'Add bKash, Nagad, or bank account details' }}</div>
    </div>
    <a class="btn light" href="{{ route('payment-accounts.index') }}">Back</a>
</div>

<form method="post" action="{{ $editing ? route('payment-accounts.update', $paymentAccount) : route('payment-accounts.store') }}" class="card form-grid">
    @csrf
    @if ($editing) @method('PUT') @endif
    <div>
        <label>Payment Method</label>
        <select name="payment_method" required>
            <option value="bkash" @selected(old('payment_method', $paymentAccount->payment_method) === 'bkash')>bKash</option>
            <option value="nagad" @selected(old('payment_method', $paymentAccount->payment_method) === 'nagad')>Nagad</option>
            <option value="bank" @selected(old('payment_method', $paymentAccount->payment_method) === 'bank')>Bank</option>
        </select>
    </div>
    <div><label>Account Name</label><input name="account_name" value="{{ old('account_name', $paymentAccount->account_name) }}" placeholder="Office bKash, Islami Bank, Main Nagad" required></div>
    <div><label>Account Number</label><input name="account_number" value="{{ old('account_number', $paymentAccount->account_number) }}" required></div>
    <div>
        <label>Opening Balance</label>
        <input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', $paymentAccount->opening_balance ?? '0.00') }}" required>
        @if ($editing)<span class="muted">Opening balance cannot be changed after transactions exist.</span>@endif
    </div>
    <div><label>Status</label><select name="status" required><option value="active" @selected(old('status', $paymentAccount->status ?? 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $paymentAccount->status) === 'inactive')>Inactive</option></select></div>
    @if ($assignableOwners->isNotEmpty())
        <div>
            <label>Owner (may transact through this account)</label>
            <select name="owner_user_id">
                <option value="">Unassigned — super admin only</option>
                @foreach ($assignableOwners as $owner)
                    <option value="{{ $owner->id }}" @selected((int) old('owner_user_id', $paymentAccount->owner_user_id) === (int) $owner->id)>
                        {{ $owner->name }} ({{ $owner->email }})
                    </option>
                @endforeach
            </select>
            <span class="muted">Only this user (and any super admin) can record money through this account.</span>
        </div>
        <div>
            <label>Balance limit (optional)</label>
            <input type="number" step="0.01" min="0" name="balance_limit" value="{{ old('balance_limit', $paymentAccount->balance_limit) }}" placeholder="No limit">
            <span class="muted">When set, the owner must deposit collected money to the office before the balance can go above this.</span>
        </div>
    @endif
    <div class="full"><button class="btn" type="submit">{{ $editing ? 'Update Account' : 'Save Account' }}</button></div>
</form>
@endsection
