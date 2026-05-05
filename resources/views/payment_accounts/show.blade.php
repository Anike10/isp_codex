@extends('layouts.app')

@section('content')
@php
    $methodLabels = [
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'bank' => 'Bank',
    ];

    $runningBalance = (float) $paymentAccount->opening_balance;
    $totalCollected = $paymentAccount->payments->sum('amount');
@endphp

<div class="topbar">
    <div>
        <h1>Account Ledger</h1>
        <div class="muted">
            {{ $methodLabels[$paymentAccount->payment_method] ?? ucfirst($paymentAccount->payment_method) }}
            · {{ $paymentAccount->account_name }}
            · {{ $paymentAccount->account_number }}
        </div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('payments.create') }}">Record Payment</a>
        <a class="btn light" href="{{ route('payment-accounts.index') }}">Back</a>
    </div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat">
        <span class="muted">Opening Balance</span>
        <strong>{{ number_format($paymentAccount->opening_balance, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Total Collection</span>
        <strong>{{ number_format($totalCollected, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Current Balance</span>
        <strong>{{ number_format((float) $paymentAccount->opening_balance + $totalCollected, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Transactions</span>
        <strong>{{ $paymentAccount->payments->count() }}</strong>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Note</th>
            <th>Credit</th>
            <th>Running Balance</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $paymentAccount->created_at?->format('Y-m-d') }}</td>
            <td>Opening</td>
            <td>N/A</td>
            <td>Opening balance</td>
            <td>{{ number_format($paymentAccount->opening_balance, 2) }}</td>
            <td>{{ number_format($runningBalance, 2) }}</td>
        </tr>
        @forelse ($paymentAccount->payments as $payment)
            @php
                $runningBalance += (float) $payment->amount;
            @endphp
            <tr>
                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                <td><a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_no }}</a></td>
                <td>{{ $payment->customer->name }}</td>
                <td>{{ $payment->note ?? 'Payment received' }}</td>
                <td>{{ number_format($payment->amount, 2) }}</td>
                <td>{{ number_format($runningBalance, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No transactions recorded for this account yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
