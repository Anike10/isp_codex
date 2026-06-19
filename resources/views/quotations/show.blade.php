@extends('layouts.app')

@section('content')
@php($serialFormatter = app(\App\Support\SerialNumberParser::class))

<div class="topbar">
    <div>
        <h1>{{ $quotation->quotation_no }}</h1>
        <div class="muted">{{ $quotation->customer->name }} - {{ $quotation->formatted_billing_month }}</div>
        <div style="margin-top:8px">
            <span class="badge {{ $quotation->status === 'converted' ? 'active' : 'due' }}">{{ ucfirst($quotation->status) }}</span>
            <span class="badge">Not included in accounting</span>
        </div>
    </div>
    <div class="actions">
        @if (! $quotation->converted_invoice_id)
            <a class="btn secondary" href="{{ route('quotations.edit', $quotation) }}">Edit</a>
            <form method="post" action="{{ route('quotations.make-invoice', $quotation) }}" onsubmit="return confirm('Create a new draft invoice from this quotation? Stock will be adjusted now.');">
                @csrf
                <button class="btn" type="submit">Make Invoice</button>
            </form>
        @else
            <a class="btn" href="{{ route('invoices.show', $quotation->convertedInvoice) }}">Open Invoice</a>
        @endif
        <a class="btn light" href="{{ route('quotations.print', $quotation) }}" target="_blank">Print</a>
        <a class="btn light" href="{{ route('quotations.index') }}">Back</a>
    </div>
</div>

<section class="card" style="margin-bottom:16px; border-left:4px solid #1570ef;">
    This document is an estimate only. It does not affect billed amount, party due, payments, or stock until an invoice is made.
</section>

<div class="grid two">
    <section class="card">
        <h2>Quotation</h2>
        <p><strong>Date:</strong> {{ $quotation->quotation_date?->format('Y-m-d') }}</p>
        <p><strong>Valid Until:</strong> {{ $quotation->valid_until?->format('Y-m-d') ?? 'Open' }}</p>
        <p><strong>Reference Month:</strong> {{ $quotation->formatted_billing_month }}</p>
        <p><strong>Subtotal:</strong> {{ number_format($quotation->subtotal, 2) }}</p>
        @if ((float) $quotation->discount > 0)<p><strong>Discount:</strong> {{ number_format($quotation->discount, 2) }}</p>@endif
        @if ((float) $quotation->vat > 0)<p><strong>VAT:</strong> {{ number_format($quotation->vat, 2) }}</p>@endif
        <p><strong>Quoted Total:</strong> {{ number_format($quotation->total, 2) }}</p>
    </section>
    <section class="card">
        <h2>Party</h2>
        <p><strong>Name:</strong> {{ $quotation->customer->name }}</p>
        <p><strong>Phone:</strong> {{ $quotation->customer->phone }}</p>
        <p><strong>Connection:</strong> {{ $quotation->customer->connection_id }}</p>
        <p><strong>Address:</strong> {{ $quotation->customer->address }}</p>
        @if ($quotation->convertedInvoice)
            <p><strong>Created Invoice:</strong> <a href="{{ route('invoices.show', $quotation->convertedInvoice) }}">{{ $quotation->convertedInvoice->invoice_no }}</a></p>
        @endif
    </section>
</div>

<section class="card" style="margin-top:16px; overflow:auto">
    <h2>Items</h2>
    <table>
        <thead><tr><th>SL</th><th>Product</th><th>Serials</th><th>Serial-less Qty</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
        @foreach ($quotation->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ filled($item->serial_numbers) ? $serialFormatter->formatCompact($item->serial_numbers) : 'N/A' }}</td>
                <td>{{ $item->serialless_quantity ?: 'N/A' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>

@if ($quotation->payment_note || $quotation->public_note || $quotation->private_note)
<section class="card" style="margin-top:16px">
    <h2>Notes</h2>
    @if ($quotation->payment_note)<p><strong>Terms & Conditions:</strong> <span style="white-space:pre-line">{{ $quotation->payment_note }}</span></p>@endif
    @if ($quotation->public_note)<p><strong>Quotation Note:</strong> <span style="white-space:pre-line">{{ $quotation->public_note }}</span></p>@endif
    @if ($quotation->private_note)<p><strong>Private Note:</strong> <span style="white-space:pre-line">{{ $quotation->private_note }}</span></p>@endif
</section>
@endif
@endsection
