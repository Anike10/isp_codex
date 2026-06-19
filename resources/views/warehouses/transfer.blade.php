@extends('layouts.app')

@section('content')
@php
    $initialTransferItems = old('items', [[
        'product_id' => request('product_id'),
        'quantity' => '',
        'serial_numbers' => '',
        'serialless_quantity' => '',
    ]]);
@endphp
<style>
    .transfer-header { margin-bottom:16px; }
    .transfer-items { display:grid; gap:14px; }
    .transfer-item { position:relative; display:grid; grid-template-columns:52px minmax(240px, 2fr) 110px 140px minmax(180px, 1fr) auto; gap:12px; align-items:end; }
    .transfer-item .serial-area { grid-column:2 / -1; }
    .transfer-item.is-dragging { opacity:.55; border-color:#116149; box-shadow:0 10px 24px rgba(17,97,73,.14); }
    .item-order { width:44px; min-height:40px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #c8d2df; border-radius:8px; background:#f8fafc; color:#172033; cursor:grab; font:inherit; font-weight:800; user-select:none; }
    .item-order:active { cursor:grabbing; }
    .serial-options { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .serial-option { border:1px solid #c8d2df; border-radius:6px; background:#fff; color:#172033; padding:5px 8px; cursor:pointer; font:inherit; font-size:12px; }
    .serial-option.is-selected { border-color:#116149; background:#edf8f4; color:#0f513e; }
    .serial-option:focus { outline:2px solid #116149; outline-offset:2px; }
    .remove-transfer-item { background:#fff0f0; color:#b42318; }
    .add-item-bar { display:flex; justify-content:space-between; gap:14px; align-items:center; margin-top:14px; padding:16px 18px; border:1px solid #d8dee9; border-radius:8px; background:#fff; }
    .add-item-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .add-item-count { display:inline-flex; align-items:center; gap:8px; margin:0; color:#475467; font-size:13px; }
    .add-item-count input { width:78px; min-height:40px; }
    @media (max-width:980px) { .transfer-item { grid-template-columns:52px 1fr 1fr; } .transfer-item .serial-area { grid-column:2 / -1; } }
    @media (max-width:560px) { .transfer-item { grid-template-columns:44px 1fr; } .transfer-item .serial-area { grid-column:1 / -1; } .add-item-bar { align-items:stretch; flex-direction:column; } }
</style>

<div class="topbar">
    <div><h1>Transfer Warehouse Stock</h1><div class="muted">Select warehouses once, then transfer any number of products together</div></div>
    <a class="btn light" href="{{ route('warehouses.index') }}">Warehouses</a>
</div>

<form method="post" action="{{ route('warehouse-transfers.store') }}" id="warehouseTransferForm">
    @csrf
    <section class="card form-grid transfer-header">
        <div>
            <label>From Warehouse</label>
            <select name="from_warehouse_id" id="fromWarehouse" required>
                <option value="">Select source</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((int)old('from_warehouse_id', request('from_warehouse_id')) === $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
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
        <div class="full"><label>Reason / Note</label><input name="reason" value="{{ old('reason') }}" placeholder="Common purpose for this transfer"></div>
    </section>

    <div class="topbar">
        <div><h2>Products</h2><div class="muted">Serial-tracked rows show only serials available in the source warehouse.</div></div>
    </div>
    <div class="transfer-items" id="transferItems"></div>

    <div class="add-item-bar">
        <div class="muted">Items: <strong id="transferItemCount">0</strong> · Drag the numbered handle to reorder rows with their selected serials.</div>
        <div class="add-item-actions">
            <label class="add-item-count" for="addTransferItemCount">Rows <input id="addTransferItemCount" type="number" min="1" max="50" value="1" inputmode="numeric"></label>
            <button class="btn secondary" type="button" id="addTransferItem">Add Item</button>
        </div>
    </div>
    <div class="actions" style="margin-top:16px"><button class="btn" type="submit">Transfer All Products</button></div>
</form>

<script>
const transferProducts = @json($productData);
const initialTransferItems = @json($initialTransferItems);
const form = document.getElementById('warehouseTransferForm');
const fromInput = document.getElementById('fromWarehouse');
const toInput = document.getElementById('toWarehouse');
const itemsContainer = document.getElementById('transferItems');
let nextItemIndex = 0;

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

function selectedSerials(row) {
    return [...new Set((row.querySelector('[data-serial-input]')?.value || '')
        .split(/[\r\n,]+/)
        .map(value => value.trim())
        .filter(Boolean)
        .flatMap(expandTransferSerialPart))];
}

function selectedProduct(row) {
    const productId = row.querySelector('[data-product-input]')?.value;
    return transferProducts.find(product => String(product.id) === String(productId));
}

function refreshDestinationOptions() {
    [...toInput.options].forEach(option => option.disabled = option.value !== '' && option.value === fromInput.value);
    if (toInput.value === fromInput.value) toInput.value = '';
}

function refreshTransferRow(row) {
    const product = selectedProduct(row);
    const sourceId = fromInput.value;
    const tracked = Boolean(product?.track_serials);
    const serialInput = row.querySelector('[data-serial-input]');
    const seriallessInput = row.querySelector('[data-serialless-input]');
    const quantityInput = row.querySelector('[data-quantity-input]');
    const options = row.querySelector('[data-serial-options]');
    const availability = row.querySelector('[data-availability]');
    const serials = selectedSerials(row);
    const serialless = parseInt(seriallessInput?.value || '0', 10) || 0;
    const sourceStock = Number(product?.stocks?.[sourceId] || 0);
    const sourceSerials = product?.serials?.filter(serial => String(serial.warehouse_id) === sourceId) || [];
    const availableSerialless = Math.max(0, sourceStock - sourceSerials.length);

    availability.textContent = product && sourceId
        ? 'Source stock: ' + sourceStock + (tracked ? ' | Serial-less available: ' + availableSerialless : '')
        : 'Select product and source warehouse';
    row.querySelectorAll('[data-serial-field]').forEach(field => field.hidden = !tracked);
    quantityInput.readOnly = tracked;
    options.innerHTML = '';

    if (tracked && sourceId) {
        sourceSerials.forEach(serial => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'serial-option';
            button.dataset.serial = serial.number;
            button.textContent = serial.number;
            button.classList.toggle('is-selected', serials.includes(serial.number));
            button.setAttribute('aria-pressed', serials.includes(serial.number) ? 'true' : 'false');
            button.addEventListener('click', () => {
                const current = selectedSerials(row);
                serialInput.value = (current.includes(serial.number)
                    ? current.filter(value => value !== serial.number)
                    : [...current, serial.number]).join(', ');
                refreshTransferRow(row);
                [...options.querySelectorAll('.serial-option')].find(option => option.dataset.serial === serial.number)?.focus();
            });
            options.appendChild(button);
        });
        quantityInput.value = serials.length + serialless > 0 ? serials.length + serialless : '';
    }
}

function reindexTransferRows() {
    const rows = [...itemsContainer.querySelectorAll('.transfer-item')];
    rows.forEach((row, index) => {
        row.querySelector('[data-product-input]').name = `items[${index}][product_id]`;
        row.querySelector('[data-quantity-input]').name = `items[${index}][quantity]`;
        row.querySelector('[data-serial-input]').name = `items[${index}][serial_numbers]`;
        row.querySelector('[data-serialless-input]').name = `items[${index}][serialless_quantity]`;
        const orderButton = row.querySelector('.item-order');
        orderButton.textContent = index + 1;
        orderButton.setAttribute('aria-label', `Drag item ${index + 1} to reorder`);
        row.querySelector('.remove-transfer-item').hidden = rows.length === 1;
    });
    document.getElementById('transferItemCount').textContent = rows.length;
}

function addTransferRow(values = {}) {
    const row = document.createElement('article');
    row.className = 'card transfer-item';
    row.dataset.itemIndex = nextItemIndex++;
    row.innerHTML = `
        <div><button type="button" class="item-order" draggable="true" aria-label="Drag item to reorder"></button></div>
        <div><label>Product</label><select data-product-input required><option value="">Select product</option></select><span class="muted" data-availability></span></div>
        <div><label>Quantity</label><input type="number" min="1" data-quantity-input required></div>
        <div data-serial-field><label>Serial-less Qty</label><input type="number" min="0" placeholder="Qty without serial" data-serialless-input></div>
        <div data-serial-field><label>Serial Numbers</label><textarea rows="2" placeholder="Select serials below or type comma-separated serials" data-serial-input></textarea></div>
        <div><button type="button" class="btn light remove-transfer-item">Remove</button></div>
        <div class="serial-area" data-serial-area data-serial-field><label>Available Serials in Source Warehouse</label><div class="serial-options" data-serial-options></div></div>`;

    const productInput = row.querySelector('[data-product-input]');
    transferProducts.forEach(product => productInput.add(new Option(product.label, product.id)));
    productInput.value = values.product_id || '';
    row.querySelector('[data-quantity-input]').value = values.quantity || '';
    row.querySelector('[data-serial-input]').value = values.serial_numbers || '';
    row.querySelector('[data-serialless-input]').value = values.serialless_quantity || '';
    productInput.addEventListener('change', () => {
        row.querySelector('[data-serial-input]').value = '';
        row.querySelector('[data-serialless-input]').value = '';
        row.querySelector('[data-quantity-input]').value = '';
        refreshTransferRow(row);
    });
    row.addEventListener('input', () => refreshTransferRow(row));
    row.querySelector('.remove-transfer-item').addEventListener('click', () => {
        row.remove();
        if (!itemsContainer.querySelector('.transfer-item')) addTransferRow();
        reindexTransferRows();
    });
    itemsContainer.appendChild(row);
    reindexTransferRows();
    refreshTransferRow(row);
}

document.getElementById('addTransferItem').addEventListener('click', () => {
    const countInput = document.getElementById('addTransferItemCount');
    const count = Math.min(50, Math.max(1, parseInt(countInput.value || '1', 10) || 1));
    for (let index = 0; index < count; index++) addTransferRow();
    countInput.value = 1;
});

itemsContainer.addEventListener('dragstart', event => {
    const handle = event.target.closest('.item-order');
    const row = handle?.closest('.transfer-item');
    if (!row) return;
    row.classList.add('is-dragging');
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', '');
});

itemsContainer.addEventListener('dragover', event => {
    const draggingRow = itemsContainer.querySelector('.transfer-item.is-dragging');
    if (!draggingRow) return;
    event.preventDefault();
    const afterElement = getDragAfterElement(itemsContainer, event.clientY);
    if (afterElement) itemsContainer.insertBefore(draggingRow, afterElement);
    else itemsContainer.appendChild(draggingRow);
});

itemsContainer.addEventListener('dragend', event => {
    event.target.closest('.transfer-item')?.classList.remove('is-dragging');
    reindexTransferRows();
});

function getDragAfterElement(container, y) {
    return [...container.querySelectorAll('.transfer-item:not(.is-dragging)')].reduce((closest, row) => {
        const box = row.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        return offset < 0 && offset > closest.offset ? {offset, element:row} : closest;
    }, {offset:Number.NEGATIVE_INFINITY, element:null}).element;
}
fromInput.addEventListener('change', () => {
    itemsContainer.querySelectorAll('[data-serial-input]').forEach(input => input.value = '');
    refreshDestinationOptions();
    itemsContainer.querySelectorAll('.transfer-item').forEach(refreshTransferRow);
});
toInput.addEventListener('change', refreshDestinationOptions);
initialTransferItems.forEach(addTransferRow);
refreshDestinationOptions();
</script>
@endsection
