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
    @php
        $approvalCandidates = $manualCandidates->isNotEmpty() ? $manualCandidates : $customers;
        $partyLabel = fn ($p) => trim($p->name
            .($p->mikrotik_username ? ' · '.$p->mikrotik_username : '')
            .($p->connection_id ? ' ('.$p->connection_id.')' : '')
            .' — '.$p->phone);
    @endphp
    <form method="post" action="{{ route('bkash-sms-payments.approve', $bkashSmsPayment) }}" class="card" style="margin-bottom:16px" id="approveForm">
        @csrf
        <label>Manual Match Party</label>
        <p class="muted" style="margin:0 0 8px 2px;">
            {{ $matchMessageHint ?? ($manualCandidates->isNotEmpty() ? 'Multiple parties match this sender number. Please choose one.' : 'Select the correct party to map this SMS.') }}
        </p>
        <datalist id="approvePartyList">
            @foreach ($approvalCandidates as $customer)
                <option value="{{ $partyLabel($customer) }}"></option>
            @endforeach
        </datalist>
        <input type="hidden" name="customer_id" id="approvePartyId" value="{{ $bkashSmsPayment->customer_id }}">
        <input type="search" list="approvePartyList" id="approvePartySearch"
            value="{{ $bkashSmsPayment->customer ? $partyLabel($bkashSmsPayment->customer) : '' }}"
            placeholder="Search party by name, username, connection ID or phone&hellip;" autocomplete="off" required
            style="display:block;width:100%;max-width:560px;margin:0 0 10px">
        <button class="btn" type="submit">Approve and Record Payment</button>
    </form>
    <script>
    (function () {
        var byLabel = @json($approvalCandidates->mapWithKeys(fn ($c) => [$partyLabel($c) => $c->id]));
        var search = document.getElementById('approvePartySearch');
        var hidden = document.getElementById('approvePartyId');

        function resolve() {
            var key = search.value.trim();
            hidden.value = byLabel[key] || '';
            if (! hidden.value && key) {
                var hits = Object.keys(byLabel).filter(function (k) { return k.toLowerCase().indexOf(key.toLowerCase()) === 0; });
                if (hits.length === 1) hidden.value = byLabel[hits[0]];
            }
        }

        search.addEventListener('input', resolve);
        search.addEventListener('change', resolve);
        document.getElementById('approveForm').addEventListener('submit', function (event) {
            resolve();
            if (! hidden.value) {
                event.preventDefault();
                search.focus();
                alert('Pick a party from the list first.');
            }
        });
    })();
    </script>
@endif

<div class="grid two">
    <section class="card">
        <h2>Parsed SMS</h2>
        <p><strong>Status:</strong> <span class="badge {{ $bkashSmsPayment->status }}">{{ $bkashSmsPayment->status_label }}</span></p>
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
