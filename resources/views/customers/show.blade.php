@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>{{ $customer->name }}</h1><div class="muted">{{ $customer->connection_id }} · {{ $customer->phone }}</div></div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('customers.edit', $customer) }}">Edit</a>
        <a class="btn light" href="{{ route('customers.index') }}">Back</a>
    </div>
</div>

<div class="grid two">
    <section class="card">
        <h2>Profile</h2>
        <p><strong>Status:</strong> <span class="badge {{ $customer->status }}">{{ $customer->status }}</span></p>
        <p><strong>Email:</strong> {{ $customer->email ?? 'Not provided' }}</p>
        <p><strong>Address:</strong> {{ $customer->address }}</p>
        <p><strong>Package:</strong> {{ $customer->activeSubscription?->package?->name ?? 'No active package' }}</p>
    </section>
    <section class="card">
        <h2>Billing Summary</h2>
        <p><strong>Total invoices:</strong> {{ $customer->invoices->count() }}</p>
        <p><strong>Total due:</strong> {{ number_format($customer->invoices->sum('due_amount'), 2) }}</p>
    </section>
</div>

<section class="card" style="margin-top:16px">
    <h2>Invoices</h2>
    <table>
        <thead><tr><th>Invoice</th><th>Month</th><th>Total</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($customer->invoices as $invoice)
            <tr>
                <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a></td>
                <td>{{ $invoice->billing_month }}</td>
                <td>{{ number_format($invoice->total, 2) }}</td>
                <td>{{ number_format($invoice->due_amount, 2) }}</td>
                <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5">No invoices yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
