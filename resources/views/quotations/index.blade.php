@extends('layouts.app')

@section('content')
@php
    $canOpenPartyLedger = auth()->user()?->hasPermission('manage_payment_accounts') || auth()->user()?->hasPermission('manage_customers');
@endphp
<div class="topbar">
    <div>
        <h1>Quotations</h1>
        <div class="muted">Prepare estimates without adding them to sales, dues, payments, or stock.</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('quotations.create') }}">Create Quotation</a>
        <a class="btn light" href="{{ route('invoices.index') }}">Invoices</a>
    </div>
</div>

<section class="card" style="margin-bottom:16px; border-left:4px solid #1570ef;">
    <strong>Accounting note:</strong> Quotation amounts are informational only. An amount enters invoice and stock accounts only after using <strong>Make Invoice</strong>.
</section>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div>
        <label>Search</label>
        <input name="search" value="{{ request('search') }}" placeholder="Quotation no, party name, or phone">
    </div>
    <div>
        <label>Status</label>
        <select name="status">
            <option value="">All statuses</option>
            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
            <option value="converted" @selected(request('status') === 'converted')>Converted</option>
        </select>
    </div>
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Valid Until Before</label><input type="date" name="valid_until" value="{{ request('valid_until') }}"></div>
    <div class="full actions">
        <button class="btn secondary" type="submit">Search</button>
        <a class="btn light" href="{{ route('quotations.index') }}">Reset</a>
    </div>
</form>

@include('partials.per_page')

<section class="card" style="overflow:auto">
    <table>
        <thead>
            <tr><th>Quotation</th><th>Date</th><th>Valid Until</th><th>Party</th><th>Total</th><th>Status</th><th>Invoice</th><th>Actions</th></tr>
        </thead>
        <tbody>
        @forelse ($quotations as $quotation)
            <tr data-href="{{ route('quotations.show', $quotation) }}">
                <td><a href="{{ route('quotations.show', $quotation) }}"><strong>{{ $quotation->quotation_no }}</strong></a></td>
                <td>{{ $quotation->quotation_date?->format('Y-m-d') }}</td>
                <td>{{ $quotation->valid_until?->format('Y-m-d') ?? 'Open' }}</td>
                <td>
                    @if ($canOpenPartyLedger)
                        <a href="{{ route('accounting.ledger', ['customer_id' => $quotation->customer_id]) }}">{{ $quotation->customer->name }}</a>
                    @else
                        {{ $quotation->customer->name }}
                    @endif
                    <br><span class="muted">{{ $quotation->customer->phone }}</span>
                </td>
                <td>{{ number_format($quotation->total, 2) }}</td>
                <td><span class="badge {{ $quotation->status === 'converted' ? 'active' : 'due' }}">{{ ucfirst($quotation->status) }}</span></td>
                <td>
                    @if ($quotation->convertedInvoice)
                        <a href="{{ route('invoices.show', $quotation->convertedInvoice) }}">{{ $quotation->convertedInvoice->invoice_no }}</a>
                    @else
                        <span class="muted">Not created</span>
                    @endif
                </td>
                <td>
                    <div class="actions">
                        <a class="btn light" href="{{ route('quotations.show', $quotation) }}">View</a>
                        <a class="btn light" href="{{ route('quotations.print', $quotation) }}" target="_blank">Print</a>
                        @if (! $quotation->converted_invoice_id)
                            <a class="btn secondary" href="{{ route('quotations.edit', $quotation) }}">Edit</a>
                            <form method="post" action="{{ route('quotations.make-invoice', $quotation) }}" onsubmit="return confirm('Create a new draft invoice from this quotation? Stock will be adjusted now.');">
                                @csrf
                                <button class="btn" type="submit">Make Invoice</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8">No quotations found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

{{ $quotations->links() }}
@endsection
