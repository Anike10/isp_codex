@extends('layouts.app')

@section('content')
<style>
    .available-serial-options {
        display: flex;
        flex: 1 0 100%;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 2px;
    }

    .available-serial-options[hidden] {
        display: none;
    }

    .available-serial-label {
        flex-basis: 100%;
        font-size: 12px;
    }

    .available-serial-option {
        border: 1px solid #c8d2df;
        border-radius: 6px;
        background: #fff;
        color: #172033;
        padding: 5px 8px;
        cursor: pointer;
        font: inherit;
        font-size: 12px;
    }

    .available-serial-option.is-selected {
        border-color: #116149;
        background: #edf8f4;
        color: #0f513e;
    }

    .available-serial-option:focus {
        outline: 2px solid #116149;
        outline-offset: 2px;
    }
</style>

<div class="topbar">
    <div><h1>Inventory</h1><div class="muted">Routers, cable, computer parts and accessories</div></div>
    <a class="btn" href="{{ route('products.create') }}">Add Product</a>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Name, SKU, barcode, brand, category"></div>
    <div>
        <label>Brand</label>
        <select name="brand">
            <option value="">All brands</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Category</label>
        <select name="product_category_id">
            <option value="">All categories</option>
            @foreach ($categoryOptions as $category)
                <option value="{{ $category->id }}" @selected((int) request('product_category_id') === $category->id)>{{ implode(' / ', $category->pathNames()) }}</option>
            @endforeach
        </select>
    </div>
    <div class="full actions">
        <button class="btn secondary" type="submit">Filter</button>
        <a class="btn light" href="{{ route('products.index') }}">Reset</a>
    </div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Product</th><th>SKU</th><th>Barcode</th><th>Brand</th><th>Category</th><th>Sub Category</th><th>Stock</th><th>Serial</th><th>Warranty</th><th>Sale Price</th><th>Move Stock</th></tr></thead>
    <tbody>
    @forelse ($products as $product)
        <tr data-href="{{ route('products.show', $product) }}">
            <td>{{ $product->name }}</td>
            <td>{{ $product->sku }}</td>
            <td>{{ $product->barcode ?? 'N/A' }}</td>
            <td>{{ $product->brand ?? 'N/A' }}</td>
            <td>{{ $product->category ?? 'N/A' }}</td>
            <td>{{ $product->subcategory ?? 'N/A' }}</td>
            <td>
                @if ($product->track_inventory)
                    {{ $product->stock_quantity }}
                    @if ($product->isLowStock())
                        <span class="badge low">low</span>
                    @endif
                @else
                    <span class="badge pending">not tracked</span>
                @endif
            </td>
            <td>{{ $product->track_serial_numbers ? 'Tracked' : 'N/A' }}</td>
            <td>{{ $product->warranty_days !== null ? $product->warranty_days.' day(s)' : 'N/A' }}</td>
            <td>{{ number_format($product->sale_price, 2) }}</td>
            <td>
                @if ($product->track_inventory)
                    <form method="post" action="{{ route('products.stock', $product) }}" class="actions product-stock-form" data-current-stock="{{ $product->stock_quantity }}" data-track-serials="{{ $product->track_serial_numbers ? '1' : '0' }}">
                        @csrf
                        <select name="type" class="movement-type" style="width:auto"><option value="in">In</option><option value="out">Out</option><option value="use">Own Use</option></select>
                        <input type="number" name="quantity" class="movement-quantity" min="1" placeholder="Qty" style="width:90px" required>
                        @if ($product->track_serial_numbers)
                            <input name="serial_numbers" class="serial-numbers" placeholder="Serials / range" aria-label="Serial numbers or range" style="width:180px">
                            <input type="number" name="serialless_quantity" class="serialless-quantity" min="0" placeholder="Serial-less Qty" aria-label="Quantity without serial" style="width:140px">
                            <div class="available-serial-options" hidden>
                                <span class="available-serial-label muted">In-stock serials ({{ $product->serials->count() }})</span>
                                @foreach ($product->serials as $serial)
                                    <button type="button" class="available-serial-option" data-serial="{{ $serial->serial_number }}" aria-pressed="false">{{ $serial->serial_number }}</button>
                                @endforeach
                            </div>
                        @endif
                        <span class="stock-before muted" hidden>Available before movement: {{ $product->stock_quantity }}</span>
                        <input name="reason" placeholder="Reason" style="width:150px">
                        <button class="btn secondary" type="submit">Update</button>
                    </form>
                @else
                    <span class="muted">N/A</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="11">No products found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $products->links() }}</div>

<script>
const serialDigitMap = {'\u09E6': '0', '\u09E7': '1', '\u09E8': '2', '\u09E9': '3', '\u09EA': '4', '\u09EB': '5', '\u09EC': '6', '\u09ED': '7', '\u09EE': '8', '\u09EF': '9'};

function normalizeSerialDigits(value) {
    return value.replace(/[\u09E6-\u09EF]/g, digit => serialDigitMap[digit]);
}

function expandSerialPart(part) {
    const match = part.match(/^([\p{L}_-]*)([0-9\u09E6-\u09EF]+)\s*(?:-|to|\u09A5\u09C7\u0995\u09C7)\s*([\p{L}_-]*)([0-9\u09E6-\u09EF]+)$/iu);

    if (!match) return [part];

    const startPrefix = match[1];
    const endPrefix = match[3] || startPrefix;
    const startText = normalizeSerialDigits(match[2]);
    const endText = normalizeSerialDigits(match[4]);
    const start = Number(startText);
    const end = Number(endText);

    if (startPrefix !== endPrefix || !Number.isInteger(start) || !Number.isInteger(end) || end < start || end - start >= 1000) {
        return [part];
    }

    const width = Math.max(startText.length, endText.length);
    const serials = [];

    for (let number = start; number <= end; number++) {
        serials.push(startPrefix + String(number).padStart(width, '0'));
    }

    return serials;
}

function syncStockForm(form) {
    const movementType = form.querySelector('.movement-type');
    const stockBefore = form.querySelector('.stock-before');
    const serialOptions = form.querySelector('.available-serial-options');
    const isOutgoing = ['out', 'use'].includes(movementType?.value);

    if (stockBefore) stockBefore.hidden = !isOutgoing;
    if (serialOptions) serialOptions.hidden = !isOutgoing;

    if (form.dataset.trackSerials !== '1') return;

    const parts = (form.querySelector('.serial-numbers')?.value || '')
        .split(/[\r\n,]+/)
        .map(value => value.trim())
        .filter(Boolean);
    const serialCount = new Set(parts.flatMap(expandSerialPart)).size;
    const seriallessCount = parseInt(form.querySelector('.serialless-quantity')?.value || '0', 10) || 0;
    const quantity = form.querySelector('.movement-quantity');
    const total = serialCount + seriallessCount;

    serialOptions?.querySelectorAll('.available-serial-option').forEach(button => {
        const selected = new Set(parts.flatMap(expandSerialPart)).has(button.dataset.serial);
        button.classList.toggle('is-selected', selected);
        button.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });

    if (quantity) quantity.value = total > 0 ? total : '';
}

document.querySelectorAll('.product-stock-form').forEach(form => {
    form.querySelector('.available-serial-options')?.addEventListener('click', event => {
        const button = event.target.closest('.available-serial-option');
        const serialInput = form.querySelector('.serial-numbers');
        const selectedSerial = button?.dataset.serial;

        if (!serialInput || !selectedSerial) return;

        const serials = [...new Set(serialInput.value
            .split(/[\r\n,]+/)
            .map(value => value.trim())
            .filter(Boolean)
            .flatMap(expandSerialPart))];
        const nextSerials = serials.includes(selectedSerial)
            ? serials.filter(serial => serial !== selectedSerial)
            : [...serials, selectedSerial];

        serialInput.value = nextSerials.join(', ');
        syncStockForm(form);
        button.focus();
    });
    form.addEventListener('input', () => syncStockForm(form));
    form.addEventListener('change', () => syncStockForm(form));
    syncStockForm(form);
});
</script>
@endsection
