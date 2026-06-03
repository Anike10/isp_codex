@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Invoices</h1>
        <div class="muted">Monthly bills and party dues</div>
        <div class="muted" style="margin-top:8px; font-size:0.95rem;">নতুন ইনভয়েস তৈরি করতে একটি মাস নির্বাচন করুন এবং Generate Bills চাপুন।</div>
        <div class="muted" style="margin-top:4px; font-size:0.95rem;">রাউটার বা অন্য পণ্যের জন্য একাধিক বিল হলে Create Invoice ব্যবহার করুন।</div>
    </div>
    <div class="actions">
        <form method="post" action="{{ route('invoices.generate') }}" class="actions">
            @csrf
            <input type="month" name="billing_month" value="{{ request('billing_month', now()->format('Y-m')) }}" required>
            <button class="btn" type="submit">Generate Bills</button>
        </form>
    </div>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div class="full">
        <label>Search</label>
        <input name="search" value="{{ request('search') }}" placeholder="Party name, mobile, connection ID, invoice no, or month">
    </div>
    <div>
        <label>Billing Month</label>
        <input type="month" name="billing_month" value="{{ request('billing_month') }}">
    </div>
    <div>
        <label>Payment Status</label>
    <select name="status">
        <option value="">All statuses</option>
        @foreach (['unpaid', 'partial', 'paid'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    </div>
    <div>
        <label>Invoice Type</label>
        <select name="invoice_type">
            <option value="">All types</option>
            @foreach (['service', 'product'] as $type)
                <option value="{{ $type }}" @selected(request('invoice_type') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Final State</label>
        <select name="final_state">
            <option value="">All</option>
            <option value="draft" @selected(request('final_state') === 'draft')>Draft</option>
            <option value="final" @selected(request('final_state') === 'final')>Final</option>
        </select>
    </div>
    <div class="full actions">
        <button class="btn secondary" type="submit">Search</button>
        <a class="btn light" href="{{ route('invoices.index') }}">Reset</a>
    </div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Invoice</th><th>Party</th><th>Mobile</th><th>Month</th><th>Type</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th>State</th><th></th></tr></thead>
    <tbody>
    @forelse ($invoices as $invoice)
        <tr data-href="{{ route('invoices.show', $invoice) }}">
            <td>{{ $invoice->invoice_no }}</td>
            <td>{{ $invoice->customer->name }}</td>
            <td>{{ $invoice->customer->phone }}</td>
            <td>{{ $invoice->formatted_billing_month }}</td>
            <td>{{ ucfirst($invoice->invoice_type ?? 'service') }}</td>
            <td>{{ number_format($invoice->total, 2) }}</td>
            <td>{{ number_format($invoice->paid_amount, 2) }}</td>
            <td>{{ number_format($invoice->due_amount, 2) }}</td>
            <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
            <td>
                @if ($invoice->isFinalized())
                    <span class="badge active">Final</span>
                @else
                    <span class="badge due">Draft</span>
                @endif
            </td>
            <td><a class="btn light" href="{{ route('invoices.show', $invoice) }}">View</a></td>
        </tr>
    @empty
        <tr><td colspan="11">No invoices found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $invoices->links() }}</div>
@endsection
