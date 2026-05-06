@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Accounting Ledger</h1>
        <div class="muted">Invoices, payments, and running totals</div>
    </div>
    <a class="btn light" href="{{ route('payment-accounts.index') }}">Back</a>
</div>

<form method="get" class="card actions" style="margin-bottom:16px">
    <input type="date" name="from" value="{{ request('from') }}">
    <input type="date" name="to" value="{{ request('to') }}">
    <button class="btn secondary" type="submit">Filter</button>
    <a class="btn light" href="{{ route('accounting.ledger') }}">Reset</a>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Total Debit</span><strong>{{ number_format($totalDebit, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Total Credit</span><strong>{{ number_format($totalCredit, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Net</span><strong>{{ number_format($totalDebit - $totalCredit, 2) }}</strong></div>
</div>

<table>
    <thead><tr><th>Date</th><th>Type</th><th>Customer</th><th>Reference</th><th>Note</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
    <tbody>
        @php $running = 0; @endphp
        @forelse ($entries as $entry)
            @php $running += $entry['debit'] - $entry['credit']; @endphp
            <tr data-href="{{ $entry['url'] }}">
                <td>{{ $entry['date']?->format('Y-m-d') }}</td>
                <td>{{ $entry['type'] }}</td>
                <td>{{ $entry['customer'] }}</td>
                <td><a href="{{ $entry['url'] }}">{{ $entry['reference'] }}</a></td>
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
