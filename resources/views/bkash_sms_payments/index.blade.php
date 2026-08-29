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

<form method="post" action="{{ route('bkash-sms-payments.whatsapp') }}" class="card" style="margin-bottom:16px;display:flex;gap:14px;align-items:end;flex-wrap:wrap">
    @csrf
    <span class="muted" style="font-weight:700">WhatsApp reply</span>
    <label class="muted" style="display:flex;gap:6px;align-items:center;font-size:12px">
        <input type="checkbox" name="enabled" value="1" @checked($whatsappEnabled)>
        Send the payer a WhatsApp confirmation after a bKash SMS is processed
    </label>
    <span class="muted" style="display:flex;gap:10px;align-items:center;font-size:12px">
        Trigger on:
        <label style="display:flex;gap:4px;align-items:center"><input type="checkbox" name="statuses[]" value="processed" @checked(in_array('processed', $whatsappStatuses, true))> Processed</label>
        <label style="display:flex;gap:4px;align-items:center"><input type="checkbox" name="statuses[]" value="balance" @checked(in_array('balance', $whatsappStatuses, true))> Balance</label>
    </span>
    <button class="btn secondary" type="submit" name="action" value="save">Save</button>
    <span style="display:flex;gap:6px;align-items:center">
        <input type="text" name="test_number" placeholder="01XXXXXXXXX" style="width:130px">
        <button class="btn light" type="submit" name="action" value="test">Send test</button>
    </span>
    <span class="muted" style="flex-basis:100%;font-size:12px">
        @if ($whatsappConfigured)
            Cloud API credentials detected. Template: <code>{{ config('services.whatsapp.payment_template') }}</code> ({{ config('services.whatsapp.payment_template_language') }}) &mdash; body vars: name, amount, TrxID, date. Needs <code>php artisan queue:work</code> running.
        @else
            <strong>Not configured.</strong> Set <code>WHATSAPP_TOKEN</code>, <code>WHATSAPP_PHONE_NUMBER_ID</code> and <code>WHATSAPP_PAYMENT_TEMPLATE</code> in <code>.env</code> first.
        @endif
    </span>
</form>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="TrxID, ref, sender number, party, invoice no, or SMS text"></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option>@foreach(['auto' => 'Processed (auto)', 'manual' => 'Processed (manual)', 'pending' => 'Pending', 'balance' => 'Balance', 'duplicate' => 'Duplicate', 'failed' => 'Failed'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Min Amount</label><input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}"></div>
    <div><label>Max Amount</label><input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('bkash-sms-payments.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

@php
    $partyLabel = fn ($p) => trim($p->name
        .($p->mikrotik_username ? ' · '.$p->mikrotik_username : '')
        .($p->connection_id ? ' ('.$p->connection_id.')' : ''));
@endphp
<datalist id="bkashPartyList">
    @foreach ($customers as $party)
        <option value="{{ $partyLabel($party) }}"></option>
    @endforeach
</datalist>
<script>window.bkashParties = @json($customers->map(fn ($p) => ['id' => $p->id, 'label' => $partyLabel($p)])->values());</script>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
            <th>TrxID</th>
            <th>Ref</th>
            <th>Amount</th>
            <th>Number</th>
            <th>Party</th>
            <th>Invoice</th>
            <th>Device</th>
            <th>Paid by</th>
            <th>Updated</th>
            <th>WhatsApp</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($smsPayments as $smsPayment)
            @php $canPay = in_array($smsPayment->status, ['pending', 'failed'], true) && $smsPayment->amount !== null && $smsPayment->trx_id; @endphp
            <tr data-href="{{ route('bkash-sms-payments.show', $smsPayment) }}">
                <td>{{ $smsPayment->created_at->format('d/m/Y H:i') }}</td>
                <td><span class="badge {{ $smsPayment->status }}">{{ $smsPayment->status_label }}</span></td>
                <td style="min-width:220px">
                    @if ($canPay)
                        <form method="post" action="{{ route('bkash-sms-payments.approve', $smsPayment) }}" class="bkash-pay-form" data-confirm="Record Tk {{ number_format($smsPayment->amount, 2) }} (TrxID {{ $smsPayment->trx_id }}) for the selected party?">
                            @csrf
                            <input type="hidden" name="redirect_to" value="index">
                            <input type="hidden" name="customer_id" class="bkash-party-id" value="{{ $smsPayment->customer_id }}">
                            <input type="search" list="bkashPartyList" class="bkash-party-search"
                                value="{{ $smsPayment->customer ? $partyLabel($smsPayment->customer) : '' }}"
                                placeholder="Search party&hellip;" autocomplete="off" required
                                style="display:block;width:100%;margin-bottom:6px">
                            <span style="display:inline-flex;gap:6px;align-items:center">
                                <button class="btn secondary" type="submit">Pay</button>
                                <a class="btn light" href="{{ route('bkash-sms-payments.show', $smsPayment) }}">Details</a>
                            </span>
                        </form>
                    @else
                        <a class="btn light" href="{{ route('bkash-sms-payments.show', $smsPayment) }}">Details</a>
                    @endif
                </td>
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
                <td>{{ $smsPayment->entry_by ?: '—' }}</td>
                <td>{{ $smsPayment->paid_by_name ?: '—' }}</td>
                <td>{{ $smsPayment->updated_at->format('d/m/Y H:i') }}</td>
                <td style="white-space:nowrap">
                    @php $waStatus = $smsPayment->whatsapp_status; @endphp
                    @if ($waStatus === 'sent')
                        <span class="badge active" title="Sent to {{ $smsPayment->whatsapp_to }} at {{ $smsPayment->whatsapp_sent_at?->format('d/m/Y H:i') }}">sent</span>
                    @elseif ($waStatus === 'failed')
                        <span class="badge failed" title="{{ $smsPayment->whatsapp_error }}">failed</span>
                    @elseif ($waStatus === 'skipped')
                        <span class="badge" title="{{ $smsPayment->whatsapp_error }}">skipped</span>
                    @else
                        <span class="muted">—</span>
                    @endif
                    @if (in_array($smsPayment->status, ['processed', 'balance'], true) && $waStatus !== 'sent')
                        <form method="post" action="{{ route('bkash-sms-payments.whatsapp-resend', $smsPayment) }}" style="display:inline">
                            @csrf
                            <button class="btn light" type="submit">{{ $waStatus ? 'Resend' : 'Send' }}</button>
                        </form>
                    @endif
                </td>
                <td>{{ $smsPayment->message ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="14">No bKash SMS payments received yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $smsPayments->links() }}</div>

<script>
(function () {
    var parties = window.bkashParties || [];
    var byLabel = new Map(parties.map(function (p) { return [p.label.toLowerCase(), p.id]; }));

    function resolveId(text) {
        var key = (text || '').trim().toLowerCase();
        if (byLabel.has(key)) return byLabel.get(key);
        // Fall back to a unique starts-with match so a half-typed name still resolves.
        var hits = parties.filter(function (p) { return p.label.toLowerCase().indexOf(key) === 0; });
        return (key && hits.length === 1) ? hits[0].id : '';
    }

    document.querySelectorAll('.bkash-pay-form').forEach(function (form) {
        var search = form.querySelector('.bkash-party-search');
        var hidden = form.querySelector('.bkash-party-id');

        search.addEventListener('input', function () { hidden.value = resolveId(search.value); });
        search.addEventListener('change', function () { hidden.value = resolveId(search.value); });

        form.addEventListener('submit', function (event) {
            hidden.value = resolveId(search.value);
            if (! hidden.value) {
                event.preventDefault();
                search.focus();
                alert('Pick a party from the list first.');
                return;
            }
            if (! confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
})();
</script>
@endsection
