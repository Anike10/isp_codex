@extends('layouts.app')

@section('content')
@php
    $canOpenPaymentAccounts = auth()->user()?->hasPermission('manage_payment_accounts');
    $canOpenCustomers = auth()->user()?->hasPermission('manage_customers');
    $printUrl = route('accounting.ledger.print', request()->except(['page', 'per_page', 'make_per_page_default']));
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
    <div class="actions">
        <a class="btn secondary" href="{{ $printUrl }}" target="_blank">Print</a>
        @if ($canOpenPaymentAccounts)
            <a class="btn light" href="{{ route('payment-accounts.index') }}">Back</a>
        @elseif ($canOpenCustomers)
            <a class="btn light" href="{{ route('customers.index') }}">Back</a>
        @endif
    </div>
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

@include('partials.per_page')

<table>
    <thead><tr><th>SL</th><th>Date</th><th>Type</th>@unless ($selectedCustomer)<th>Party</th>@endunless<th>Note</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
    <tbody>
        @forelse ($entries as $entry)
            <tr @if ($entry['url']) data-href="{{ $entry['url'] }}" @endif>
                <td>{{ $entry['serial'] }}</td>
                <td>{{ $entry['date']?->format('d/m/Y') }}</td>
                <td>{{ $entry['type'] }}</td>
                @unless ($selectedCustomer)<td>{{ $entry['customer'] }}</td>@endunless
                <td>{{ $entry['note'] }}</td>
                <td>{{ number_format($entry['debit'], 2) }}</td>
                <td>{{ number_format($entry['credit'], 2) }}</td>
                <td>{{ number_format($entry['balance'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $selectedCustomer ? 7 : 8 }}">No ledger entries found.</td></tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $entries->links() }}</div>
@endsection
