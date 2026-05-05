@extends('layouts.app')

@section('content')
@php
    $methodLabels = [
        'cash' => 'Cash',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'bank' => 'Bank',
    ];
@endphp

<div class="topbar">
    <div>
        <h1>Payment Accounts</h1>
        <div class="muted">Payment method details and current balances</div>
    </div>
    <a class="btn" href="{{ route('payment-accounts.create') }}">Add Account</a>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat">
        <span class="muted">Cash Balance</span>
        <strong>{{ number_format($cashCollected, 2) }}</strong>
    </div>
    @foreach (['bkash', 'nagad', 'bank'] as $method)
        @php
            $methodAccounts = $allAccounts->where('payment_method', $method);
            $opening = $methodAccounts->sum(fn ($account) => (float) $account->opening_balance);
            $collected = $methodAccounts->sum(fn ($account) => (float) ($account->collected_amount ?? 0));
        @endphp
        <div class="card stat">
            <span class="muted">{{ $methodLabels[$method] }} Balance</span>
            <strong>{{ number_format($opening + $collected, 2) }}</strong>
        </div>
    @endforeach
</div>

@include('partials.per_page')

<table>
    <thead>
        <tr>
            <th>Method</th>
            <th>Account Name</th>
            <th>Account Number</th>
            <th>Opening Balance</th>
            <th>Collected</th>
            <th>Current Balance</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <tr data-href="{{ route('payment-accounts.cash-ledger') }}">
            <td>Cash</td>
            <td>Cash Collection</td>
            <td>N/A</td>
            <td>{{ number_format(0, 2) }}</td>
            <td>{{ number_format($cashCollected, 2) }}</td>
            <td>{{ number_format($cashCollected, 2) }}</td>
            <td><span class="badge active">active</span></td>
            <td><a class="btn light" href="{{ route('payment-accounts.cash-ledger') }}">Ledger</a></td>
        </tr>
        @forelse ($accounts as $account)
            @php
                $collected = (float) ($account->collected_amount ?? 0);
                $currentBalance = (float) $account->opening_balance + $collected;
            @endphp
            <tr data-href="{{ route('payment-accounts.show', $account) }}">
                <td>{{ $methodLabels[$account->payment_method] ?? ucfirst($account->payment_method) }}</td>
                <td>{{ $account->account_name }}</td>
                <td>{{ $account->account_number }}</td>
                <td>{{ number_format($account->opening_balance, 2) }}</td>
                <td>{{ number_format($collected, 2) }}</td>
                <td>{{ number_format($currentBalance, 2) }}</td>
                <td><span class="badge {{ $account->status }}">{{ $account->status }}</span></td>
                <td><a class="btn light" href="{{ route('payment-accounts.show', $account) }}">Ledger</a></td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No bKash, Nagad, or bank accounts added yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $accounts->links() }}</div>
@endsection
