@extends('layouts.app')

@section('content')
@php
    $runningBalance = 0;
@endphp

<div class="topbar">
    <div>
        <h1>Cash Ledger</h1>
        <div class="muted">All cash collection transactions with running balance</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('payments.create') }}">Record Payment</a>
        <a class="btn light" href="{{ route('payment-accounts.index') }}">Back</a>
    </div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat">
        <span class="muted">Opening Balance</span>
        <strong>{{ number_format(0, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Total Cash Collection</span>
        <strong>{{ number_format($totalCollected, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Current Cash Balance</span>
        <strong>{{ number_format($totalCollected - $totalSpent, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Total Cash Expense</span>
        <strong>{{ number_format($totalSpent, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Transactions</span>
        <strong>{{ $payments->total() }}</strong>
    </div>
</div>

@include('partials.per_page')

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
            <td>N/A</td>
            <td>Opening</td>
            <td>N/A</td>
            <td>Opening balance</td>
            <td>{{ number_format(0, 2) }}</td>
            <td>{{ number_format($runningBalance, 2) }}</td>
        </tr>
        @forelse ($payments as $payment)
            @php
                $runningBalance += (float) $payment->amount;
            @endphp
            <tr>
                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                <td><a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_no }}</a></td>
                <td>{{ $payment->customer->name }}</td>
                <td>{{ $payment->note ?? 'Cash payment received' }}</td>
                <td>{{ number_format($payment->amount, 2) }}</td>
                <td>{{ number_format($runningBalance, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No cash transactions recorded yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $payments->links() }}</div>
@endsection
