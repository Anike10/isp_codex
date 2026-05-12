@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Payments</h1><div class="muted">Customer collection history</div></div>
    <a class="btn" href="{{ route('payments.create') }}">Record Payment</a>
</div>

@include('partials.per_page')

<table>
    <thead><tr><th>Date</th><th>Customer</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Account</th><th></th></tr></thead>
    <tbody>
    @forelse ($payments as $payment)
        <tr data-href="{{ route('invoices.show', $payment->invoice) }}">
            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
            <td>{{ $payment->customer->name }}</td>
            <td><a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_no }}</a></td>
            <td>{{ number_format($payment->amount, 2) }}</td>
            <td>{{ $payment->payment_method }}</td>
            <td>{{ $payment->account ? $payment->account->account_name.' - '.$payment->account->account_number : 'N/A' }}</td>
            <td><a class="btn light" href="{{ route('payments.voucher', $payment) }}">Voucher</a></td>
        </tr>
    @empty
        <tr><td colspan="7">No payments recorded.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $payments->links() }}</div>
@endsection
