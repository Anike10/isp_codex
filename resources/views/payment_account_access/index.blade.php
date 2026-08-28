@extends('layouts.app')

@section('content')
<style>
    .pa-access-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:8px; margin-top:10px; }
    .pa-access-grid label { display:flex; gap:8px; align-items:flex-start; padding:8px 10px; border:1px solid var(--line); border-radius:8px; background:#fff; }
    .pa-access-grid input { width:auto; margin-top:3px; }
    .pa-access-grid small { display:block; color:var(--muted); }
</style>

<div class="topbar">
    <div>
        <h1>Payment Account Access</h1>
        <div class="muted">Choose which admins may record money through each account. The owner always has access.</div>
    </div>
    <a class="btn light" href="{{ route('payment-accounts.index') }}">Payment Accounts</a>
</div>

@forelse ($accounts as $account)
    @php($delegateIds = $account->delegates->pluck('id')->all())
    <div class="card" style="margin-bottom:14px">
        <h2 style="margin-bottom:2px">
            {{ $account->account_name }}
            <span class="muted">({{ ucfirst($account->payment_method) }} &middot; {{ $account->account_number }})</span>
        </h2>
        <div class="muted">Owner: {{ $account->owner?->name ?? 'Unassigned — super admin only' }}</div>
        <form method="post" action="{{ route('payment-account-access.update', $account) }}">
            @csrf @method('PUT')
            <div class="pa-access-grid">
                @foreach ($users as $user)
                    @continue($account->owner_user_id && (int) $user->id === (int) $account->owner_user_id)
                    <label>
                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" @checked(in_array($user->id, $delegateIds, true))>
                        <span>
                            <strong>{{ $user->name }}</strong>
                            <small>{{ $user->email }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="action-row" style="margin-top:10px">
                <button class="btn secondary" type="submit">Save access</button>
            </div>
        </form>
    </div>
@empty
    <div class="card muted">No bKash, Nagad, or bank accounts have been added yet.</div>
@endforelse
@endsection
