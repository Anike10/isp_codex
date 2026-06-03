@extends('layouts.app')

@section('content')
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
                <td>{{ $smsPayment->customer?->name ?? 'N/A' }}</td>
                <td>
                    @if ($smsPayment->invoice)
                        <a href="{{ route('invoices.show', $smsPayment->invoice) }}">{{ $smsPayment->invoice->invoice_no }}</a>
                    @else
                        N/A
                    @endif
                </td>
                <td>
                    @if ($smsPayment->payment)
                        Payment #{{ $smsPayment->payment->id }}
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
