@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $purchaseBill->bill_no }}</h1>
        <div class="muted">{{ $purchaseBill->purchase_date->format('Y-m-d') }} - {{ $purchaseBill->party?->name ?? 'No vendor selected' }}</div>
        <div style="margin-top:8px">
            @if ($purchaseBill->isFinalized())
                <span class="badge active">Final</span>
            @else
                <span class="badge due">Draft</span>
            @endif
        </div>
    </div>
    <div class="actions">
        @if (! $purchaseBill->isFinalized())
            <a class="btn secondary" href="{{ route('purchase-bills.edit', $purchaseBill) }}">Edit</a>
            <form method="post" action="{{ route('purchase-bills.finalize', $purchaseBill) }}" onsubmit="return confirm('Finalize this purchase bill? You will not be able to edit it after finalizing.');">
                @csrf
                <button class="btn" type="submit">Final</button>
            </form>
        @endif
        <a class="btn light" href="{{ route('purchase-bills.index') }}">Back</a>
    </div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Vendor Party</span><strong style="font-size:18px">{{ $purchaseBill->party?->name ?? 'N/A' }}</strong></div>
    <div class="card stat"><span class="muted">Items</span><strong>{{ $purchaseBill->items->count() }}</strong></div>
    <div class="card stat"><span class="muted">Total</span><strong>{{ number_format($purchaseBill->subtotal, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Date</span><strong style="font-size:18px">{{ $purchaseBill->purchase_date->format('Y-m-d') }}</strong></div>
    <div class="card stat"><span class="muted">Finalized</span><strong style="font-size:18px">{{ $purchaseBill->finalized_at?->format('Y-m-d H:i') ?? 'Not finalized' }}</strong></div>
</div>

@if ($purchaseBill->note)
<section class="card" style="margin-bottom:16px">
    <h2>Note</h2>
    <p style="white-space:pre-line">{{ $purchaseBill->note }}</p>
</section>
@endif

<section class="card">
    <h2>Purchased Products</h2>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Serial-less Qty</th><th>Unit</th><th>Total</th><th>Warranty</th><th>Serials</th></tr></thead>
        <tbody>
        @foreach ($purchaseBill->items as $item)
            <tr>
                <td>
                    {{ $item->product->name }} <span class="muted">{{ $item->product->sku }}</span>
                    <div class="muted">{{ $item->product->brand ?? 'No brand' }} - {{ $item->product->category ?? 'No category' }}{{ $item->product->subcategory ? ' / '.$item->product->subcategory : '' }}</div>
                </td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->serialless_quantity ?: 'N/A' }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
                <td>
                    @if ($item->warranty_days)
                        {{ $item->warranty_days }} day(s)
                    @elseif ($item->warranty_months)
                        {{ $item->warranty_months }} month(s)
                    @else
                        No warranty
                    @endif
                </td>
                <td>
                    @forelse ($item->serials as $serial)
                        <div><span class="badge">{{ $serial->serial_number }}</span> <span class="muted">{{ $serial->warranty_until ? 'Warranty until '.$serial->warranty_until->format('Y-m-d') : 'No warranty date' }}</span></div>
                    @empty
                        <span class="muted">No serial tracked</span>
                    @endforelse
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>

@include('partials.record_versions', ['versions' => $versions])
@endsection
