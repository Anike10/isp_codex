@extends('layouts.app')

@section('content')
@php
    $canOpenCustomers = auth()->user()?->hasPermission('manage_customers');
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
@endphp
<div class="topbar">
    <div>
        <h1>bKash SMS Details</h1>
        <div class="muted">{{ $bkashSmsPayment->trx_id ?? 'No TrxID' }} - {{ $bkashSmsPayment->created_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('bkash-sms-payments.create') }}">Manual Entry</a>
        <a class="btn light" href="{{ route('bkash-sms-payments.index') }}">Back</a>
    </div>
</div>

@if (in_array($bkashSmsPayment->status, ['pending', 'failed'], true))
    <form method="post" action="{{ route('bkash-sms-payments.approve', $bkashSmsPayment) }}" class="card form-grid" style="margin-bottom:16px">
        @csrf
        <div class="full">
            <label>Manual Match Party</label>
            <select name="customer_id" required>
                <option value="">Select party</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">
                        {{ $customer->name }} - {{ $customer->phone }} - {{ $customer->mikrotik_username ?? $customer->connection_id }}
                        @if ($customer->is_customer || $customer->is_vendor)
                            - {{ collect([$customer->is_customer ? 'Customer' : null, $customer->is_vendor ? 'Vendor' : null])->filter()->implode(' + ') }}
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="full">
            <button class="btn" type="submit">Approve and Record Payment</button>
        </div>
    </form>
@endif

<div class="grid two">
    <section class="card">
        <h2>Parsed SMS</h2>
        <p><strong>Status:</strong> <span class="badge {{ $bkashSmsPayment->status }}">{{ $bkashSmsPayment->status }}</span></p>
        <p><strong>Amount:</strong> {{ $bkashSmsPayment->amount !== null ? number_format($bkashSmsPayment->amount, 2) : 'N/A' }}</p>
        <p><strong>From Number:</strong> {{ $bkashSmsPayment->customer_number ?? 'N/A' }}</p>
        <p><strong>Reference:</strong> {{ $bkashSmsPayment->reference ?? 'N/A' }}</p>
        <p><strong>TrxID:</strong> {{ $bkashSmsPayment->trx_id ?? 'N/A' }}</p>
        <p><strong>Payment Date:</strong> {{ $bkashSmsPayment->payment_date?->format('d/m/Y') ?? 'N/A' }}</p>
        <p><strong>SMS Sender:</strong> {{ $bkashSmsPayment->sms_sender ?? 'N/A' }}</p>
    </section>

    <section class="card">
        <h2>Updates</h2>
        <p><strong>Party:</strong>
            @if ($bkashSmsPayment->customer)
                @if ($canOpenCustomers)
                    <a href="{{ route('customers.show', $bkashSmsPayment->customer) }}">{{ $bkashSmsPayment->customer->name }} - {{ $bkashSmsPayment->customer->connection_id }}</a>
                @else
                    {{ $bkashSmsPayment->customer->name }} - {{ $bkashSmsPayment->customer->connection_id }}
                @endif
            @else
                N/A
            @endif
        </p>
        <p><strong>Invoice:</strong>
            @if ($bkashSmsPayment->invoice)
                @if ($canOpenInvoices)
                    <a href="{{ route('invoices.show', $bkashSmsPayment->invoice) }}">{{ $bkashSmsPayment->invoice->invoice_no }}</a>
                @else
                    {{ $bkashSmsPayment->invoice->invoice_no }}
                @endif
            @else
                N/A
            @endif
        </p>
        <p><strong>Payment:</strong> {{ $bkashSmsPayment->payment ? '#'.$bkashSmsPayment->payment->id.' recorded' : 'N/A' }}</p>
        <p><strong>Ledger Update:</strong>
            @if ($bkashSmsPayment->payment)
                Payment ledger updated.
            @elseif ($bkashSmsPayment->status === 'balance')
                Party account balance updated.
            @elseif ($bkashSmsPayment->status === 'duplicate')
                Duplicate TrxID. Ledger was not updated.
            @else
                No ledger update.
            @endif
        </p>
        <p><strong>Message:</strong> {{ $bkashSmsPayment->message ?? 'N/A' }}</p>
    </section>
</div>

<section class="card" style="margin-top:16px">
    <h2>Raw SMS</h2>
    <p style="white-space:pre-wrap">{{ $bkashSmsPayment->raw_sms }}</p>
</section>
@endsection
