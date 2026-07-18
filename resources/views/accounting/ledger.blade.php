@extends('layouts.app')

@section('content')
@php
    $canOpenPaymentAccounts = auth()->user()?->hasPermission('manage_payment_accounts');
    $canOpenCustomers = auth()->user()?->hasPermission('manage_customers');
@endphp
<div class="topbar">
    <div>
        <h1>{{ $selectedCustomer ? 'Party Ledger' : 'Accounting Ledger' }}</h1>
        <div class="muted">
            @if ($selectedCustomer)
                {{ $selectedCustomer->name }} - invoices, payments, advance balance, and running totals
            @else
                Invoices, payments, salaries, expenses, and running totals
            @endif
        </div>
    </div>
    @if ($canOpenPaymentAccounts)
        <a class="btn light" href="{{ route('payment-accounts.index') }}">Back</a>
    @elseif ($canOpenCustomers)
        <a class="btn light" href="{{ route('customers.index') }}">Back</a>
    @endif
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    @if ($selectedCustomer)
        <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
    @endif
    <input type="date" name="from" value="{{ request('from') }}">
    <input type="date" name="to" value="{{ request('to') }}">
    <div class="actions">
        <button class="btn secondary" type="submit">Filter</button>
        <a class="btn light" href="{{ $selectedCustomer ? route('accounting.ledger', ['customer_id' => $selectedCustomer->id]) : route('accounting.ledger') }}">Reset</a>
        @if ($selectedCustomer)
            @if ($canOpenPaymentAccounts)
                <a class="btn light" href="{{ route('accounting.ledger') }}">All Ledger</a>
            @endif
            @if ($canOpenCustomers)
                <a class="btn light" href="{{ route('customers.show', $selectedCustomer) }}">Party Details</a>
                <a class="btn" href="{{ route('customers.payments.create', $selectedCustomer) }}">Record Payment</a>
            @endif
        @endif
    </div>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Total Debit</span><strong>{{ number_format($totalDebit, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Total Credit</span><strong>{{ number_format($totalCredit, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Net</span><strong>{{ number_format($totalDebit - $totalCredit, 2) }}</strong></div>
</div>

<table>
    <thead><tr><th>Date</th><th>Type</th><th>Party</th><th>Reference</th><th>Note</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
    <tbody>
        @php $running = 0; @endphp
        @forelse ($entries as $entry)
            @php $running += $entry['debit'] - $entry['credit']; @endphp
            <tr @if ($entry['url']) data-href="{{ $entry['url'] }}" @endif>
                <td>{{ $entry['date']?->format('Y-m-d') }}</td>
                <td>{{ $entry['type'] }}</td>
                <td>{{ $entry['customer'] }}</td>
                <td>
                    @if ($entry['url'])
                        <a href="{{ $entry['url'] }}">{{ $entry['reference'] }}</a>
                    @else
                        {{ $entry['reference'] }}
                    @endif
                </td>
                <td>{{ $entry['note'] }}</td>
                <td>{{ number_format($entry['debit'], 2) }}</td>
                <td>{{ number_format($entry['credit'], 2) }}</td>
                <td>{{ number_format($running, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No ledger entries found.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
