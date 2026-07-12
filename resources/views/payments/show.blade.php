@extends('layouts.app')

@section('content')
@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $allocations = $payment->allocations->sortBy([
        ['invoice.due_date', 'asc'],
        ['invoice.id', 'asc'],
    ]);
@endphp

<div class="topbar">
    <div>
        <h1>Payment #{{ $payment->id }}</h1>
        <div class="muted">{{ $payment->customer->name }} - {{ $payment->payment_date?->format('Y-m-d') }}</div>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('payments.voucher', $payment) }}">Voucher</a>
        <a class="btn secondary" href="{{ route('payments.thermal-voucher', $payment) }}">Thermal</a>
        <a class="btn light" href="{{ route('payments.index') }}">Back</a>
    </div>
</div>

<div class="grid two">
    <section class="card">
        <h2>Payment Details</h2>
        <p><strong>Amount:</strong> {{ number_format($payment->amount, 2) }}</p>
        <p><strong>Date:</strong> {{ $payment->payment_date?->format('Y-m-d') }}</p>
        <p><strong>Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
        <p><strong>Account:</strong> {{ $payment->account ? $payment->account->account_name.' - '.$payment->account->account_number : 'Cash Ledger' }}</p>
        <p><strong>Reference:</strong> Payment #{{ $payment->id }}</p>
        <p><strong>Primary Invoice:</strong>
            @if ($canOpenInvoices)
                <a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_no }}</a>
            @else
                {{ $payment->invoice->invoice_no }}
            @endif
        </p>
    </section>

    <section class="card">
        <h2>Party</h2>
        <p><strong>Name:</strong> {{ $payment->customer->name }}</p>
        <p><strong>Phone:</strong> {{ $payment->customer->phone }}</p>
        <p><strong>Connection:</strong> {{ $payment->customer->connection_id ?: 'N/A' }}</p>
        <p><strong>Account Balance:</strong> {{ number_format($payment->customer->account_balance, 2) }}</p>
    </section>
</div>

<section class="card" style="margin-top:16px">
    <h2>Invoice Allocations</h2>
    <table>
        <thead><tr><th>Invoice</th><th>Bill Month</th><th>Date</th><th>Allocated Amount</th><th>Note</th></tr></thead>
        <tbody>
        @forelse ($allocations as $allocation)
            <tr @if ($canOpenInvoices) data-href="{{ route('invoices.show', $allocation->invoice) }}" @endif>
                <td>
                    @if ($canOpenInvoices)
                        <a href="{{ route('invoices.show', $allocation->invoice) }}">{{ $allocation->invoice->invoice_no }}</a>
                    @else
                        {{ $allocation->invoice->invoice_no }}
                    @endif
                </td>
                <td>{{ $allocation->invoice->formatted_billing_month }}</td>
                <td>{{ $allocation->allocated_at?->format('Y-m-d') }}</td>
                <td>{{ number_format($allocation->amount, 2) }}</td>
                <td>{{ $allocation->note }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No invoice allocations found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

@if ($payment->note)
    <section class="card" style="margin-top:16px">
        <h2>Note</h2>
        <p style="white-space:pre-line">{{ $payment->note }}</p>
    </section>
@endif
@endsection
