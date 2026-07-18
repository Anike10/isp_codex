@extends('layouts.app')

@section('content')
@php
    $canOpenPartyLedger = auth()->user()?->hasPermission('manage_payment_accounts') || auth()->user()?->hasPermission('manage_customers');
    $canOpenPaymentAccountLedger = auth()->user()?->hasPermission('manage_payment_accounts');
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
@endphp
<div class="topbar">
    <div><h1>Payments</h1><div class="muted">Party collection history</div></div>
    <a class="btn" href="{{ route('payments.create') }}">Record Payment</a>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Party, phone, connection ID, invoice no, month, account, or note"></div>
    <div><label>Method</label><select name="payment_method"><option value="">All methods</option>@foreach(['cash'=>'Cash','bkash'=>'bKash','nagad'=>'Nagad','bank'=>'Bank'] as $value=>$label)<option value="{{ $value }}" @selected(request('payment_method') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Account</label><select name="payment_account_id"><option value="">All accounts</option>@foreach($paymentAccounts as $account)<option value="{{ $account->id }}" @selected((int) request('payment_account_id') === $account->id)>{{ ucfirst($account->payment_method) }} - {{ $account->account_name }} - {{ $account->account_number }}</option>@endforeach</select></div>
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Min Amount</label><input type="number" step="0.01" name="min_amount" value="{{ request('min_amount') }}"></div>
    <div><label>Max Amount</label><input type="number" step="0.01" name="max_amount" value="{{ request('max_amount') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('payments.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Payment Date</th><th>Party</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Account</th><th>Entered By</th><th>Entered At</th><th></th></tr></thead>
    <tbody>
    @forelse ($payments as $payment)
        <tr data-href="{{ route('payments.show', $payment) }}">
            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
            <td>
                @if ($canOpenPartyLedger)
                    <a href="{{ route('accounting.ledger', ['customer_id' => $payment->customer_id]) }}">{{ $payment->customer->name }}</a>
                @else
                    {{ $payment->customer->name }}
                @endif
            </td>
            <td>
                @if ($canOpenInvoices)
                    <a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_no }}</a>
                @else
                    {{ $payment->invoice->invoice_no }}
                @endif
            </td>
            <td>{{ number_format($payment->amount, 2) }}</td>
            <td>{{ $payment->payment_method }}</td>
            <td>
                @if ($payment->account)
                    @if ($canOpenPaymentAccountLedger)
                        <a href="{{ route('payment-accounts.show', $payment->account) }}">{{ $payment->account->account_name.' - '.$payment->account->account_number }}</a>
                    @else
                        {{ $payment->account->account_name.' - '.$payment->account->account_number }}
                    @endif
                @elseif ($payment->payment_method === 'cash')
                    @if ($canOpenPaymentAccountLedger)
                        <a href="{{ route('payment-accounts.cash-ledger') }}">Cash Ledger</a>
                    @else
                        Cash
                    @endif
                @else
                    N/A
                @endif
            </td>
            <td>{{ $payment->entered_by_label }}</td>
            <td>{{ $payment->created_at?->format('Y-m-d h:i:s A') }}</td>
            <td>
                <div class="action-group">
                    <a class="btn light" href="{{ route('payments.show', $payment) }}">Details</a>
                    <a class="btn light" href="{{ route('payments.voucher', $payment) }}">Voucher</a>
                    <a class="btn light" href="{{ route('payments.thermal-voucher', $payment) }}">Thermal</a>
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="9">No invoice payments recorded.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $payments->links() }}</div>

<section class="card" style="margin-top:24px">
    <h2>Advance Collections</h2>
    <div class="muted" style="margin-bottom:12px">Collections received when no due invoice was available. These amounts were saved directly in the party advance ledger.</div>
    <table>
        <thead><tr><th>Payment Date</th><th>Party</th><th>Amount</th><th>Method</th><th>Account</th><th>Balance After</th><th>Entered By</th><th>Entered At</th><th>Reference / Note</th></tr></thead>
        <tbody>
        @forelse($advanceCredits as $credit)
            <tr>
                <td>{{ $credit->transaction_date?->format('Y-m-d') }}</td>
                <td>
                    @if($canOpenPartyLedger)
                        <a href="{{ route('accounting.ledger', ['customer_id' => $credit->customer_id]) }}">{{ $credit->customer->name }}</a>
                    @else
                        {{ $credit->customer->name }}
                    @endif
                </td>
                <td>{{ number_format($credit->amount, 2) }}</td>
                <td>{{ ucfirst((string) $credit->payment_method) }}</td>
                <td>
                    @if($credit->account)
                        @if($canOpenPaymentAccountLedger)<a href="{{ route('payment-accounts.show', $credit->account) }}">{{ $credit->account->account_name.' - '.$credit->account->account_number }}</a>@else{{ $credit->account->account_name.' - '.$credit->account->account_number }}@endif
                    @elseif($credit->payment_method === 'cash')
                        @if($canOpenPaymentAccountLedger)<a href="{{ route('payment-accounts.cash-ledger') }}">Cash Ledger</a>@else Cash @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ number_format($credit->balance_after, 2) }}</td>
                <td>{{ $credit->entered_by_label }}</td>
                <td>{{ $credit->created_at?->format('Y-m-d h:i:s A') }}</td>
                <td>{{ collect([$credit->reference, $credit->note])->filter()->implode(' · ') ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="9">No direct advance collections recorded.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:16px">{{ $advanceCredits->links() }}</div>
</section>
@endsection
