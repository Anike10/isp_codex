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

<form method="post" action="{{ route('bkash-sms-payments.maintenance') }}" class="card" style="margin-bottom:16px;display:flex;gap:14px;align-items:end;flex-wrap:wrap">
    @csrf
    <div>
        <label class="muted" style="display:block;font-size:12px">Auto-delete rows older than</label>
        <span style="display:flex;gap:6px;align-items:center">
            <input type="number" name="retention_days" min="0" max="3650" value="{{ $retentionDays }}" style="width:90px"> day(s)
        </span>
    </div>
    <label class="muted" style="display:flex;gap:6px;align-items:center;font-size:12px">
        <input type="checkbox" name="autodelete_junk" value="1" @checked($junkAutoDelete)>
        Auto-delete junk failed SMS (not bKash / Nagad / any provider payment)
    </label>
    <button class="btn secondary" type="submit" name="action" value="save">Save</button>
    <button class="btn light" type="submit" name="action" value="prune_old"
        onclick="return confirm('Delete every SMS row older than the retention window now?')">Delete old rows now</button>
    <button class="btn light" type="submit" name="action" value="delete_failed"
        onclick="return confirm('Delete ALL {{ $failedCount }} failed SMS row(s) now?')">Delete all failed ({{ $failedCount }})</button>
    <button class="btn light" type="submit" name="action" value="prune_junk"
        onclick="return confirm('Delete {{ $junkFailedCount }} non-payment junk failed SMS row(s) now?')">Delete junk failed ({{ $junkFailedCount }})</button>
    <span class="muted" style="flex-basis:100%;font-size:12px"><strong>0 = keep forever.</strong> Retention and junk cleanup also run automatically every night. "Junk" = a failed SMS with no TrxID and no amount (OTP, promo, etc.).</span>
</form>

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

<template id="bkashPartyOptions">
    <option value="">Select party&hellip;</option>
    @foreach ($customers as $party)
        <option value="{{ $party->id }}">{{ $party->name }}@if ($party->connection_id) ({{ $party->connection_id }})@endif</option>
    @endforeach
</template>

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
            <th>Paid by</th>
            <th>Updated</th>
            <th>Message</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($smsPayments as $smsPayment)
            @php $canPay = in_array($smsPayment->status, ['pending', 'failed'], true) && $smsPayment->amount !== null && $smsPayment->trx_id; @endphp
            <tr data-href="{{ route('bkash-sms-payments.show', $smsPayment) }}">
                <td>{{ $smsPayment->created_at->format('d/m/Y H:i') }}</td>
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
                <td>{{ $smsPayment->paid_by_name ?? ($smsPayment->entry_by ?? '—') }}</td>
                <td>{{ $smsPayment->updated_at->format('d/m/Y H:i') }}</td>
                <td>{{ $smsPayment->message ?? 'N/A' }}</td>
                <td>
                    @if ($canPay)
                        <form method="post" action="{{ route('bkash-sms-payments.approve', $smsPayment) }}" class="bkash-pay-form" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:6px"
                            onsubmit="return this.customer_id.value ? confirm('Record Tk {{ number_format($smsPayment->amount, 2) }} (TrxID {{ $smsPayment->trx_id }}) for the selected party?') : (alert('Choose a party first.'), false)">
                            @csrf
                            <input type="hidden" name="redirect_to" value="index">
                            <select name="customer_id" class="bkash-party-select" required style="max-width:190px"><option value="">Select party&hellip;</option></select>
                            <button class="btn secondary" type="submit">Pay</button>
                        </form>
                    @endif
                    <a class="btn light" href="{{ route('bkash-sms-payments.show', $smsPayment) }}">Details</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="12">No bKash SMS payments received yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $smsPayments->links() }}</div>

<script>
(function () {
    var tpl = document.getElementById('bkashPartyOptions');
    if (! tpl) return;

    function fill(select) {
        if (select.dataset.filled) return;
        select.dataset.filled = '1';
        var current = select.value;
        select.innerHTML = '';
        select.appendChild(tpl.content.cloneNode(true));
        select.value = current;
    }

    document.querySelectorAll('.bkash-party-select').forEach(function (select) {
        select.addEventListener('focus', function () { fill(select); }, { once: false });
        select.addEventListener('mousedown', function () { fill(select); }, { once: false });
    });
})();
</script>
@endsection
