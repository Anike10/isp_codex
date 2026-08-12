@extends('layouts.app')

@section('content')
@php($serialFormatter = app(\App\Support\SerialNumberParser::class))
<div class="topbar">
    <div><h1>{{ $saleReturn->return_no }}</h1><div class="muted">{{ $saleReturn->return_date->format('d/m/Y') }} - {{ $saleReturn->customer->name }}</div></div>
    <div class="actions">
        <a class="btn light" href="{{ route('invoices.show', $saleReturn->invoice) }}">Invoice</a>
        <a class="btn light" href="{{ route('sale-returns.index') }}">Back</a>
    </div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Invoice</span><strong style="font-size:18px">{{ $saleReturn->invoice->invoice_no }}</strong></div>
    <div class="card stat"><span class="muted">Party</span><strong style="font-size:18px">{{ $saleReturn->customer->name }}</strong></div>
    <div class="card stat"><span class="muted">Items</span><strong>{{ $saleReturn->items->count() }}</strong></div>
    <div class="card stat"><span class="muted">Returned Goods Value</span><strong>{{ number_format($saleReturn->subtotal, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Total Return Credit</span><strong>{{ number_format($saleReturn->credit_total, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Applied to Invoice</span><strong>{{ number_format($saleReturn->invoice_credit_amount, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Added to Advance</span><strong>{{ number_format($saleReturn->advance_credit_amount, 2) }}</strong></div>
</div>

@if ($saleReturn->note)
    <section class="card" style="margin-bottom:16px">
        <h2>Note</h2>
        <p style="white-space:pre-line">{{ $saleReturn->note }}</p>
    </section>
@endif

<section class="card">
    <h2>Returned Products</h2>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Serial-less Qty</th><th>Serials</th><th>Unit</th><th>Total</th></tr></thead>
        <tbody>
        @foreach ($saleReturn->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->serialless_quantity ?: 'N/A' }}</td>
                <td>{{ filled($item->serial_numbers) ? $serialFormatter->formatCompact($item->serial_numbers) : 'N/A' }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>
@endsection
