@extends('layouts.app')

@section('content')
@php
    $canOpenPartyLedger = auth()->user()?->hasPermission('manage_payment_accounts') || auth()->user()?->hasPermission('manage_customers');
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
@endphp
<div class="topbar">
    <div>
        <h1>bKash SMS Payments</h1>
        <div class="muted">Auto parsed SMS payment logs</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('bkash-sms-payments.create') }}">Manual Entry</a>
        <a class="btn light" href="{{ route('payments.index') }}">Back</a>
    </div>
</div>

<section class="card" style="margin-bottom:16px">
    <h2>SMS Forwarder Setup</h2>
    <p><strong>Webhook URL:</strong> {{ url('/api/bkash/sms') }}</p>
    <p><strong>LAN URL for phone:</strong> http://192.168.7.246/isp_codex/public/api/bkash/sms</p>
    <p><strong>Method:</strong> POST</p>
    <p><strong>Header:</strong> X-SMS-Token: your token from BKASH_SMS_WEBHOOK_TOKEN</p>
    <p><strong>Body:</strong> message=full SMS text, sender=bKash</p>
</section>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="TrxID, ref, sender number, party, invoice no, or SMS text"></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option>@foreach(['pending','processed','balance','duplicate','failed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Min Amount</label><input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}"></div>
    <div><label>Max Amount</label><input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('bkash-sms-payments.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Status</th>
            <th>TrxID</th>
            <th>Ref</th>
            <th>Amount</th>
            <th>Number</th>
            <th>Party</th>
            <th>Invoice</th>
            <th>Updated</th>
            <th>Message</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($smsPayments as $smsPayment)
            <tr data-href="{{ route('bkash-sms-payments.show', $smsPayment) }}">
                <td>{{ $smsPayment->created_at->format('Y-m-d H:i') }}</td>
                <td><span class="badge {{ $smsPayment->status }}">{{ $smsPayment->status }}</span></td>
                <td>{{ $smsPayment->trx_id ?? 'N/A' }}</td>
                <td>{{ $smsPayment->reference ?? 'N/A' }}</td>
                <td>{{ $smsPayment->amount !== null ? number_format($smsPayment->amount, 2) : 'N/A' }}</td>
                <td>{{ $smsPayment->customer_number ?? 'N/A' }}</td>
                <td>
                    @if ($smsPayment->customer)
                        @if ($canOpenPartyLedger)
                            <a href="{{ route('accounting.ledger', ['customer_id' => $smsPayment->customer_id]) }}">{{ $smsPayment->customer->name }}</a>
                        @else
                            {{ $smsPayment->customer->name }}
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if ($smsPayment->invoice)
                        @if ($canOpenInvoices)
                            <a href="{{ route('invoices.show', $smsPayment->invoice) }}">{{ $smsPayment->invoice->invoice_no }}</a>
                        @else
                            {{ $smsPayment->invoice->invoice_no }}
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if ($smsPayment->payment)
                        <a href="{{ route('payments.show', $smsPayment->payment) }}">Payment #{{ $smsPayment->payment->id }}</a>
                    @elseif ($smsPayment->status === 'balance')
                        Party balance
                    @elseif ($smsPayment->status === 'duplicate')
                        No update
                    @else
                        Pending
                    @endif
                </td>
                <td>{{ $smsPayment->message ?? 'N/A' }}</td>
                <td><a class="btn light" href="{{ route('bkash-sms-payments.show', $smsPayment) }}">Details</a></td>
            </tr>
        @empty
            <tr>
                <td colspan="11">No bKash SMS payments received yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $smsPayments->links() }}</div>
@endsection
