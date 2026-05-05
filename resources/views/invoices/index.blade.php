@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Invoices</h1>
        <div class="muted">Monthly bills and customer dues</div>
        <div class="muted" style="margin-top:8px; font-size:0.95rem;">নতুন ইনভয়েস তৈরি করতে একটি মাস নির্বাচন করুন এবং Generate Bills চাপুন।</div>
        <div class="muted" style="margin-top:4px; font-size:0.95rem;">রাউটার বা অন্য পণ্যের জন্য একাধিক বিল হলে Create Invoice ব্যবহার করুন।</div>
    </div>
    <div class="actions">
        <form method="post" action="{{ route('invoices.generate') }}" class="actions">
            @csrf
            <input type="month" name="billing_month" value="{{ request('billing_month', now()->format('Y-m')) }}" required>
            <button class="btn" type="submit">Generate Bills</button>
        </form>
        <a class="btn secondary" href="{{ route('invoices.create') }}">Create Invoice</a>
    </div>
</div>

<form method="get" class="card actions" style="margin-bottom:16px">
    <input type="month" name="billing_month" value="{{ request('billing_month') }}">
    <select name="status">
        <option value="">All statuses</option>
        @foreach (['unpaid', 'partial', 'paid'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button class="btn secondary" type="submit">Filter</button>
    <a class="btn light" href="{{ route('invoices.index') }}">Reset</a>
</form>

<table>
    <thead><tr><th>Invoice</th><th>Customer</th><th>Month</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($invoices as $invoice)
        <tr>
            <td>{{ $invoice->invoice_no }}</td>
            <td>{{ $invoice->customer->name }}</td>
            <td>{{ $invoice->billing_month }}</td>
            <td>{{ number_format($invoice->total, 2) }}</td>
            <td>{{ number_format($invoice->paid_amount, 2) }}</td>
            <td>{{ number_format($invoice->due_amount, 2) }}</td>
            <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
            <td><a class="btn light" href="{{ route('invoices.show', $invoice) }}">View</a></td>
        </tr>
    @empty
        <tr><td colspan="8">No invoices found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $invoices->links() }}</div>
@endsection
