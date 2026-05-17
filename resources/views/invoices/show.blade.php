@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $invoice->invoice_no }}</h1>
        <div class="muted">{{ $invoice->customer->name }} - {{ $invoice->formatted_billing_month }}</div>
        <div style="margin-top:8px">
            @if ($invoice->isFinalized())
                <span class="badge active">Final</span>
            @else
                <span class="badge due">Draft</span>
            @endif
        </div>
    </div>
    <div class="actions">
        @if (! $invoice->isFinalized())
            <a class="btn secondary" href="{{ route('invoices.edit', $invoice) }}">Edit</a>
            @if (auth()->user()?->hasPermission('finalize_invoices'))
                <form method="post" action="{{ route('invoices.finalize', $invoice) }}" onsubmit="return confirm('Finalize this invoice? You will not be able to edit it after finalizing.');">
                    @csrf
                    <button class="btn" type="submit">Final</button>
                </form>
            @endif
        @endif
        <a class="btn light" href="{{ route('invoices.challan', $invoice) }}" target="_blank">Print Bill</a>
        <a class="btn light" href="{{ route('invoices.quotation', $invoice) }}" target="_blank">Print Quotation</a>
        <a class="btn light" href="{{ route('invoices.delivery-challan', $invoice) }}" target="_blank">Print Challan</a>
        <form method="post" action="{{ route('invoices.copy-next-month', $invoice) }}" onsubmit="return confirm('Copy this invoice for next month with same items and prices?');">
            @csrf
            <button class="btn light" type="submit">Copy for Next Month</button>
        </form>
        <a class="btn light" href="{{ route('invoices.index') }}">Back</a>
    </div>
</div>

<div class="grid two">
    <section class="card">
        <h2>Invoice</h2>
        <p><strong>Subtotal:</strong> {{ number_format($invoice->subtotal, 2) }}</p>
        @if ((float) $invoice->discount > 0)
            <p><strong>Discount:</strong> {{ number_format($invoice->discount, 2) }}</p>
        @endif
        <p><strong>VAT:</strong> {{ number_format($invoice->vat ?? 0, 2) }}</p>
        <p><strong>Total:</strong> {{ number_format($invoice->total, 2) }}</p>
        <p><strong>Paid:</strong> {{ number_format($invoice->paid_amount, 2) }}</p>
        <p><strong>Due:</strong> {{ number_format($invoice->due_amount, 2) }}</p>
        <p><strong>Status:</strong> <span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></p>
        <p><strong>Finalized:</strong> {{ $invoice->finalized_at?->format('Y-m-d H:i') ?? 'Not finalized' }}</p>
    </section>
    <section class="card">
        <h2>Customer</h2>
        <p><strong>Name:</strong> {{ $invoice->customer->name }}</p>
        <p><strong>Phone:</strong> {{ $invoice->customer->phone }}</p>
        <p><strong>Connection:</strong> {{ $invoice->customer->connection_id }}</p>
        <p><strong>Account Balance:</strong> {{ number_format($invoice->customer->account_balance, 2) }}</p>
        <p><strong>Address:</strong> {{ $invoice->customer->address }}</p>
    </section>
</div>

@if($invoice->items->count() > 0)
<section class="card" style="margin-top:16px">
    <h2>Items</h2>
    <table>
        <thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
        @foreach ($invoice->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>
@endif

<section class="card" style="margin-top:16px">
    <h2>Payment Allocations</h2>
    <table>
        <thead><tr><th>Date</th><th>Amount</th><th>Source</th><th>Account</th><th>Note</th></tr></thead>
        <tbody>
        @forelse ($invoice->allocations as $allocation)
            <tr>
                <td>{{ $allocation->allocated_at->format('Y-m-d') }}</td>
                <td>{{ number_format($allocation->amount, 2) }}</td>
                <td>{{ $allocation->source_type === 'advance' ? 'Advance Balance' : 'Payment #'.$allocation->payment_id }}</td>
                <td>{{ $allocation->payment?->account ? $allocation->payment->account->account_name.' - '.$allocation->payment->account->account_number : 'N/A' }}</td>
                <td>{{ $allocation->note }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No allocations yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
