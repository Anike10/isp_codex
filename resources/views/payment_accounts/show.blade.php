@extends('layouts.app')

@section('content')
@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canRecordPayments = auth()->user()?->hasPermission('manage_payments');
    $methodLabels = [
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'bank' => 'Bank',
    ];

    $hasFilters = request()->filled('from')
        || request()->filled('to')
        || request()->filled('search')
        || request()->filled('min_amount')
        || request()->filled('max_amount');
    $hasPriorPage = (int) request('page', 1) > 1;
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
        @if ($canRecordPayments)
            <a class="btn secondary" href="{{ route('payments.create') }}">Record Payment</a>
        @endif
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
        <strong>{{ number_format((float) $paymentAccount->opening_balance + $totalCollected - $totalSpent, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Total Expense</span>
        <strong>{{ number_format($totalSpent, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">Transactions</span>
        <strong>{{ $filteredTransactions }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">{{ $hasFilters ? 'Filtered Collection' : 'Listed Collection' }}</span>
        <strong>{{ number_format($filteredCollected, 2) }}</strong>
    </div>
    <div class="card stat">
        <span class="muted">{{ $hasFilters ? 'Filtered Expense' : 'Listed Expense' }}</span>
        <strong>{{ number_format($filteredSpent, 2) }}</strong>
    </div>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div>
        <label>From Date</label>
        <input type="date" name="from" value="{{ request('from') }}">
    </div>
    <div>
        <label>To Date</label>
        <input type="date" name="to" value="{{ request('to') }}">
    </div>
    <div class="full">
        <label>Search</label>
        <input name="search" value="{{ request('search') }}" placeholder="Party name, mobile, connection ID, invoice no, month, or note">
    </div>
    <div>
        <label>Min Amount</label>
        <input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}">
    </div>
    <div>
        <label>Max Amount</label>
        <input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}">
    </div>
    <div class="full actions">
        <button class="btn secondary" type="submit">Search</button>
        <a class="btn light" href="{{ route('payment-accounts.show', $paymentAccount) }}">Reset</a>
        @if ($hasFilters)
            <span class="muted">{{ $filteredTransactions }} matched, {{ number_format($filteredCollected, 2) }} collected, {{ number_format($filteredSpent, 2) }} spent.</span>
        @endif
    </div>
</form>

@include('partials.per_page')

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Invoice</th>
            <th>Party</th>
            <th>Note</th>
            <th>Credit</th>
            <th>Debit</th>
            <th>Running Balance</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ request('from') ?: $paymentAccount->created_at?->format('Y-m-d') }}</td>
            <td>{{ request()->filled('from') ? 'Before Filter' : ($hasPriorPage ? 'Before Page' : 'Opening') }}</td>
            <td>N/A</td>
            <td>{{ request()->filled('from') ? 'Opening balance plus prior collection minus prior expense' : ($hasPriorPage ? 'Balance before this page' : 'Opening balance') }}</td>
            <td>{{ number_format((request()->filled('from') || $hasPriorPage) ? $runningBalance : (float) $paymentAccount->opening_balance, 2) }}</td>
            <td>{{ number_format(0, 2) }}</td>
            <td>{{ number_format($runningBalance, 2) }}</td>
        </tr>
        @forelse ($ledgerRows as $row)
            @php
                $runningBalance += (float) $row['signed_amount'];
            @endphp
            <tr>
                <td>{{ $row['date']?->format('Y-m-d') }}</td>
                <td>
                    @if ($row['type'] === 'payment' && $row['invoice'])
                        @if ($canOpenInvoices)
                            <a href="{{ route('invoices.show', $row['invoice']) }}">{{ $row['invoice']->invoice_no }}</a>
                        @else
                            {{ $row['invoice']->invoice_no }}
                        @endif
                    @else
                        Expense
                    @endif
                </td>
                <td>{{ $row['party'] }}</td>
                <td>{{ $row['note'] }}</td>
                <td>{{ number_format($row['credit'], 2) }}</td>
                <td>{{ number_format($row['debit'], 2) }}</td>
                <td>{{ number_format($runningBalance, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No transactions recorded for this account yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $ledgerRows->links() }}</div>
@endsection
