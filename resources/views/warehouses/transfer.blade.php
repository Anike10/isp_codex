@extends('layouts.app')

@section('content')
<style>
    .serial-options { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .serial-option { border:1px solid #c8d2df; border-radius:6px; background:#fff; color:#172033; padding:5px 8px; cursor:pointer; font:inherit; font-size:12px; }
    .serial-option.is-selected { border-color:#116149; background:#edf8f4; color:#0f513e; }
    .serial-option:focus { outline:2px solid #116149; outline-offset:2px; }
</style>

<div class="topbar">
    <div><h1>Transfer Warehouse Stock</h1><div class="muted">Move product stock without changing total inventory</div></div>
    <a class="btn light" href="{{ route('warehouses.index') }}">Warehouses</a>
</div>

<form method="post" action="{{ route('warehouse-transfers.store') }}" class="card form-grid" id="warehouseTransferForm">
    @csrf
    <div>
        <label>Product</label>
        <select name="product_id" id="transferProduct" required>
            <option value="">Select product</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected((int)old('product_id', request('product_id')) === $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>From Warehouse</label>
        <select name="from_warehouse_id" id="fromWarehouse" required>
            <option value="">Select source</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((int)old('from_warehouse_id', request('from_warehouse_id')) === $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
        <span class="muted" id="sourceAvailability"></span>
    </div>
    <div>
        <label>To Warehouse</label>
        <select name="to_warehouse_id" id="toWarehouse" required>
            <option value="">Select destination</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((int)old('to_warehouse_id') === $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Quantity</label>
        <input type="number" name="quantity" id="transferQuantity" min="1" value="{{ old('quantity') }}" required>
    </div>
    <div class="serial-field">
        <label>Serial Numbers</label>
        <textarea name="serial_numbers" id="transferSerials" rows="2" placeholder="Select serials below or type comma-separated serials">{{ old('serial_numbers') }}</textarea>
    </div>
    <div class="serial-field">
        <label>Serial-less Qty</label>
        <input type="number" name="serialless_quantity" id="transferSerialless" min="0" value="{{ old('serialless_quantity') }}" placeholder="Qty without serial">
    </div>
    <div class="full serial-field">
        <label>Available Serials in Source Warehouse</label>
        <div class="serial-options" id="transferSerialOptions"></div>
    </div>
    <div class="full"><label>Reason / Note</label><input name="reason" value="{{ old('reason') }}" placeholder="Transfer purpose"></div>
    <div class="full actions"><button class="btn" type="submit">Transfer Stock</button></div>
</form>

<script>
const transferProducts = @json($productData);
const form = document.getElementById('warehouseTransferForm');
const productInput = document.getElementById('transferProduct');
const fromInput = document.getElementById('fromWarehouse');
const toInput = document.getElementById('toWarehouse');
const quantityInput = document.getElementById('transferQuantity');
const serialInput = document.getElementById('transferSerials');
const seriallessInput = document.getElementById('transferSerialless');
const serialOptions = document.getElementById('transferSerialOptions');
const availability = document.getElementById('sourceAvailability');

function selectedProduct() {
    return transferProducts.find(product => String(product.id) === productInput.value);
}

function selectedSerials() {
    return [...new Set(serialInput.value.split(/[\r\n,]+/).map(value => value.trim()).filter(Boolean).flatMap(expandTransferSerialPart))];
}

function expandTransferSerialPart(part) {
    const match = part.match(/^(.*?)(\d+)\s*(?:-|to)\s*(.*?)(\d+)$/i);

    if (!match) return [part];

    const startPrefix = match[1];
    const endPrefix = match[3] || startPrefix;
    const start = Number(match[2]);
    const end = Number(match[4]);

    if (startPrefix !== endPrefix || end < start || end - start >= 1000) return [part];

    const width = Math.max(match[2].length, match[4].length);
    const serials = [];
    for (let number = start; number <= end; number++) serials.push(startPrefix + String(number).padStart(width, '0'));

    return serials;
}

function setSelectedSerials(serials) {
    serialInput.value = [...new Set(serials)].join(', ');
}

function refreshTransferForm() {
    const product = selectedProduct();
    const sourceId = fromInput.value;
    const tracked = Boolean(product?.track_serials);
    const serials = selectedSerials();
    const serialless = parseInt(seriallessInput.value || '0', 10) || 0;
    const sourceStock = Number(product?.stocks?.[sourceId] || 0);

    availability.textContent = product && sourceId ? `Available: ${sourceStock}` : '';
    document.querySelectorAll('.serial-field').forEach(field => field.hidden = !tracked);
    serialOptions.innerHTML = '';

    if (tracked && sourceId) {
        product.serials
            .filter(serial => String(serial.warehouse_id) === sourceId)
            .forEach(serial => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'serial-option';
                button.textContent = serial.number;
                button.classList.toggle('is-selected', serials.includes(serial.number));
                button.setAttribute('aria-pressed', serials.includes(serial.number) ? 'true' : 'false');
                button.addEventListener('click', () => {
                    const current = selectedSerials();
                    setSelectedSerials(current.includes(serial.number)
                        ? current.filter(value => value !== serial.number)
                        : [...current, serial.number]);
                    refreshTransferForm();
                    serialOptions.querySelector(`[data-serial="${CSS.escape(serial.number)}"]`)?.focus();
                });
                button.dataset.serial = serial.number;
                serialOptions.appendChild(button);
            });
        quantityInput.value = serials.length + serialless > 0 ? serials.length + serialless : '';
    }

    quantityInput.readOnly = tracked;
    [...toInput.options].forEach(option => option.disabled = option.value !== '' && option.value === sourceId);
    if (toInput.value === sourceId) toInput.value = '';
}

[productInput, fromInput, toInput, serialInput, seriallessInput].forEach(input => {
    input.addEventListener('input', refreshTransferForm);
    input.addEventListener('change', refreshTransferForm);
});
refreshTransferForm();
</script>
@endsection
