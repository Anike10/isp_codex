@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Sale Returns</h1><div class="muted">Returned sold items, restored stock, and customer credit history</div></div>
    <a class="btn" href="{{ route('sale-returns.create') }}">New Sale Return</a>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Return no, invoice no, party name, phone, connection ID, or note"></div>
    <div><label>Party</label><select name="customer_id"><option value="">All parties</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((int) request('customer_id') === $customer->id)>{{ $customer->name }} - {{ $customer->phone }}</option>@endforeach</select></div>
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('sale-returns.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Return</th><th>Invoice</th><th>Party</th><th>Date</th><th>Total Credit</th><th>Note</th></tr></thead>
    <tbody>
    @forelse ($saleReturns as $saleReturn)
        <tr data-href="{{ route('sale-returns.show', $saleReturn) }}">
            <td><strong>{{ $saleReturn->return_no }}</strong></td>
            <td><a href="{{ route('invoices.show', $saleReturn->invoice) }}">{{ $saleReturn->invoice->invoice_no }}</a></td>
            <td>{{ $saleReturn->customer->name }}</td>
            <td>{{ $saleReturn->return_date->format('Y-m-d') }}</td>
            <td>{{ number_format($saleReturn->subtotal, 2) }}</td>
            <td>{{ $saleReturn->note ?? 'N/A' }}</td>
        </tr>
    @empty
        <tr><td colspan="6">No sale returns found.</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $saleReturns->links() }}</div>
@endsection
