@extends('layouts.app')

@section('content')
@php
    $defaultWarehouseId = $warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id;
    $issueItems = old('items', [[
        'product_id' => request('product_id', ''),
        'source_condition' => request('source_condition', 'new'),
        'warehouse_id' => $defaultWarehouseId,
        'quantity' => '',
        'unit_price' => '',
        'serial_numbers' => '',
        'serialless_quantity' => 0,
    ]]);
@endphp
<style>
    .issue-page { display:grid; gap:18px; }
    .issue-hero { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:18px; align-items:end; padding:22px; border:1px solid #d8dee9; border-radius:8px; background:linear-gradient(135deg,rgba(17,97,73,.10),rgba(29,118,201,.08)),#fff; }
    .issue-hero h1 { font-size:30px; }
    .issue-hero p { max-width:700px; margin:8px 0 0; line-height:1.5; }
    .issue-shell { display:grid; grid-template-columns:minmax(0,1fr) 310px; gap:18px; align-items:start; }
    .issue-panel,.issue-sidebar { border:1px solid #d8dee9; border-radius:8px; background:#fff; overflow:hidden; }
    .issue-section-head { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:18px 20px; border-bottom:1px solid #e6ebf2; background:#fbfcfe; }
    .issue-section-head h2 { margin:0; font-size:18px; }
    .issue-section-body { padding:20px; }
    .issue-header-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
    .issue-items { display:grid; gap:12px; }
    .issue-item { padding:16px; border:1px solid #e1e7ef; border-radius:8px; background:#fff; }
    .issue-item-top { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
    .issue-item-grid { display:grid; grid-template-columns:minmax(210px,1.6fr) 135px 155px 85px 110px 120px; gap:12px; align-items:start; }
    .product-picker { position:relative; }
    .product-suggestions { display:none; position:absolute; z-index:30; top:calc(100% + 4px); left:0; right:0; max-height:280px; overflow:auto; border:1px solid #d8dee9; border-radius:8px; background:#fff; box-shadow:0 14px 30px rgba(15,23,42,.16); }
    .product-suggestion { display:block; width:100%; padding:10px 12px; border:0; border-bottom:1px solid #edf0f4; background:#fff; text-align:left; cursor:pointer; }
    .product-suggestion:hover,.product-suggestion.is-active { background:#eef6f3; }
    .product-suggestion strong,.product-suggestion span { display:block; }
    .product-suggestion span { margin-top:3px; color:#667085; font-size:12px; }
    .issue-item-serials { margin-top:12px; }
    .asset-serial-options { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .asset-serial-option { border:1px solid var(--line); border-radius:6px; background:#fff; padding:5px 8px; cursor:pointer; }
    .asset-serial-option.is-selected { border-color:#116149; background:#edf8f4; color:#0f513e; }
    .stock-note { display:block; margin-top:5px; color:#667085; font-size:12px; }
    .issue-sidebar { position:sticky; top:16px; }
    .issue-sidebar-body { padding:20px; display:grid; gap:16px; }
    .summary-row { display:flex; justify-content:space-between; gap:12px; padding-bottom:10px; border-bottom:1px solid #edf0f4; }
    .report-links { display:grid; gap:8px; }
    .report-links a { display:flex; justify-content:space-between; padding:11px 12px; border:1px solid #d8dee9; border-radius:7px; color:inherit; text-decoration:none; background:#fbfcfe; }
    @media (max-width:1050px) { .issue-shell { grid-template-columns:1fr; } .issue-sidebar { position:static; } .issue-item-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:650px) { .issue-hero,.issue-header-grid,.issue-item-grid { grid-template-columns:1fr; } }
</style>

<form method="post" action="{{ route('in-house-use.store') }}" id="assetIssueForm" class="issue-page" enctype="multipart/form-data">
    @csrf
    <div class="issue-hero">
        <div>
            <h1>Create In-house Issue</h1>
            <p class="muted">Invoice entry-এর মতো employee নির্বাচন করে এক বা একাধিক product/serial একবারে issue করুন। প্রতিটি issue employee-এর নামে track হবে।</p>
        </div>
        <div class="actions"><a class="btn light" href="{{ route('products.index') }}">Products</a><button class="btn" type="submit">Save Issue</button></div>
    </div>

    <div class="issue-shell">
        <main style="display:grid;gap:18px">
            <section class="issue-panel">
                <div class="issue-section-head"><div><h2>Employee & Handover</h2><div class="muted">Who receives the items and why</div></div></div>
                <div class="issue-section-body issue-header-grid">
                    <div><label>Employee</label><select name="employee_id" id="issueEmployee" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string)old('employee_id', request('employee_id')) === (string)$employee->id)>{{ $employee->name }}{{ $employee->designation ? ' - '.$employee->designation : '' }}</option>@endforeach</select></div>
                    <div><label>Issue Date</label><input type="date" name="assigned_at" value="{{ old('assigned_at', now()->toDateString()) }}" required></div>
                    <div><label>Purpose / Location</label><input name="purpose" value="{{ old('purpose') }}" placeholder="Office desk, field team, POP, etc."></div>
                    <div><label>Handover Note</label><textarea name="note" rows="3" placeholder="Invoice note-এর মতো বিস্তারিত handover note">{{ old('note') }}</textarea></div>
                    <div class="full">
                        <label>Approval Scan / PDF</label>
                        <input type="file" name="approval_document" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <span class="muted">অনুমোদনের scan copy দিন। PDF/JPG/PNG/WEBP, সর্বোচ্চ 10 MB।</span>
                    </div>
                </div>
            </section>

            <section class="issue-panel">
                <div class="issue-section-head">
                    <div><h2>Issue Items</h2><div class="muted">Select new stock or returned used stock for each row</div></div>
                    <div class="actions"><label style="margin:0">Rows <input id="addItemCount" type="number" min="1" max="20" value="1" style="width:70px"></label><button type="button" class="btn secondary" id="addIssueItem">Add Item</button></div>
                </div>
                <div class="issue-section-body">
                    <div id="issueItems" class="issue-items"></div>
                </div>
            </section>
        </main>

        <aside class="issue-sidebar">
            <div class="issue-section-head"><h2>Issue Summary</h2></div>
            <div class="issue-sidebar-body">
                <div class="summary-row"><span class="muted">Employee</span><strong id="summaryEmployee">Not selected</strong></div>
                <div class="summary-row"><span class="muted">Product Rows</span><strong id="summaryRows">1</strong></div>
                <div class="summary-row"><span class="muted">Total Units</span><strong id="summaryUnits">0</strong></div>
                <div class="summary-row"><span class="muted">Total Asset Value</span><strong>BDT <span id="summaryValue">0.00</span></strong></div>
                <button class="btn" type="submit" style="width:100%">Save In-house Issue</button>
                <div>
                    <strong>Reports</strong>
                    <div class="report-links" style="margin-top:8px">
                        <a href="{{ route('in-house-use.report.employees') }}"><span>Employee Assets</span><span>→</span></a>
                        <a href="{{ route('in-house-use.report.used-stock') }}"><span>Returned Used Stock</span><span>→</span></a>
                        <a href="{{ route('in-house-use.report.history') }}"><span>Issue / Return History</span><span>→</span></a>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</form>

<script>
const assetProducts = @json($productOptions);
const warehouseOptions = @json($warehouses->map(fn($warehouse) => ['id' => $warehouse->id, 'name' => $warehouse->name, 'default' => $warehouse->is_default])->values());
const initialIssueItems = @json($issueItems);
const defaultWarehouseId = @json((string)$defaultWarehouseId);
const itemsContainer = document.getElementById('issueItems');

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
}

function expandedSerials(value) {
    return [...new Set(String(value || '').split(/[\r\n,]+/).map(part => part.trim()).filter(Boolean).flatMap(part => {
        const match = part.match(/^(.*?)(\d+)\s*(?:-|to)\s*(.*?)(\d+)$/i);
        if (!match) return [part];
        const startPrefix = match[1], endPrefix = match[3] || startPrefix, start = Number(match[2]), end = Number(match[4]);
        if (startPrefix !== endPrefix || end < start || end - start > 999) return [part];
        const width = Math.max(match[2].length, match[4].length);
        return Array.from({length:end-start+1},(_,index)=>startPrefix+String(start+index).padStart(width,'0'));
    }))];
}

function productSearchText(product) {
    return [product.name, product.sku, product.barcode, product.brand].filter(Boolean).join(' ').toLowerCase();
}

function productLabel(product) {
    return [product.sku, product.barcode, product.brand, `Cost ${Number(product.purchase_price || 0).toFixed(2)}`, product.track_serials ? 'Serial tracked' : 'No serial'].filter(Boolean).join(' - ');
}

function warehouseOptionsHtml(selected = '') {
    return warehouseOptions.map(warehouse => `<option value="${warehouse.id}" ${String(warehouse.id) === String(selected || defaultWarehouseId) ? 'selected' : ''}>${escapeHtml(warehouse.name)}</option>`).join('');
}

function addIssueRow(values = {}) {
    const initialProduct = assetProducts.find(product => String(product.id) === String(values.product_id));
    const initialProductName = values.product_name || initialProduct?.name || '';
    const initialUnitPrice = values.unit_price !== undefined && values.unit_price !== '' ? values.unit_price : (initialProduct?.purchase_price ?? '');
    const row = document.createElement('div');
    row.className = 'issue-item';
    row.innerHTML = `
        <div class="issue-item-top"><strong class="item-number">Item</strong><button type="button" class="btn light remove-issue-item">Remove</button></div>
        <div class="issue-item-grid">
            <div><label>Product Name</label><div class="product-picker"><input type="hidden" class="item-product-id" value="${escapeHtml(values.product_id)}"><input type="text" class="item-product-search" value="${escapeHtml(initialProductName)}" placeholder="Type product name, SKU, barcode or brand" autocomplete="off" required><div class="product-suggestions"></div></div></div>
            <div><label>Stock Type</label><select class="item-condition" required><option value="new" ${values.source_condition !== 'used' ? 'selected' : ''}>New Stock</option><option value="used" ${values.source_condition === 'used' ? 'selected' : ''}>Returned Used</option></select></div>
            <div><label>Warehouse</label><select class="item-warehouse" required>${warehouseOptionsHtml(values.warehouse_id)}</select><span class="stock-note item-availability">Select product</span></div>
            <div><label>Quantity</label><input type="number" class="item-quantity" min="1" value="${escapeHtml(values.quantity)}" required></div>
            <div><label>Unit Price</label><input type="number" class="item-unit-price" min="0" step="0.01" value="${escapeHtml(initialUnitPrice)}" required></div>
            <div><label>Total</label><input type="number" class="item-total" min="0" step="0.01" readonly tabindex="-1"></div>
            <div class="item-serialless-wrap" hidden><label>Serial-less Qty</label><input type="number" class="item-serialless" min="0" value="${escapeHtml(values.serialless_quantity ?? 0)}"></div>
        </div>
        <div class="issue-item-serials" hidden><label>Serial Numbers</label><textarea class="item-serials" rows="2" placeholder="Click serial below or enter 1001-1010, 1020">${escapeHtml(values.serial_numbers)}</textarea><div class="asset-serial-options"></div></div>`;
    itemsContainer.appendChild(row);
    bindIssueRow(row);
    syncIssueRow(row, false);
    reindexIssueRows();
    return row;
}

function selectedProduct(row) {
    return assetProducts.find(product => String(product.id) === row.querySelector('.item-product-id').value);
}

function hideProductSuggestions(row) {
    const suggestions = row.querySelector('.product-suggestions');
    suggestions.style.display = 'none';
    suggestions.innerHTML = '';
}

function renderProductSuggestions(row) {
    const input = row.querySelector('.item-product-search');
    const suggestions = row.querySelector('.product-suggestions');
    const query = input.value.trim().toLowerCase();
    const matches = assetProducts.filter(product => !query || productSearchText(product).includes(query)).slice(0, 10);
    suggestions.innerHTML = '';
    matches.forEach(product => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'product-suggestion';
        button.innerHTML = `<strong>${escapeHtml(product.name)}</strong><span>${escapeHtml(productLabel(product))}</span>`;
        button.addEventListener('click', () => selectProduct(row, product));
        suggestions.appendChild(button);
    });
    if (matches.length === 0) suggestions.innerHTML = '<div class="product-suggestion"><strong>No inventory product found</strong><span>Select an inventory product to issue stock.</span></div>';
    suggestions.style.display = 'block';
}

function selectProduct(row, product) {
    row.querySelector('.item-product-id').value = product.id;
    row.querySelector('.item-product-search').value = product.name;
    row.querySelector('.item-unit-price').value = Number(product.purchase_price || 0).toFixed(2);
    hideProductSuggestions(row);
    syncIssueRow(row, true);
}

function syncIssueRow(row, clearSerials = false) {
    const product = selectedProduct(row), condition = row.querySelector('.item-condition').value, warehouseId = row.querySelector('.item-warehouse').value;
    const serialInput = row.querySelector('.item-serials'), seriallessInput = row.querySelector('.item-serialless'), quantityInput = row.querySelector('.item-quantity');
    if (clearSerials) { serialInput.value = ''; seriallessInput.value = 0; quantityInput.value = ''; }
    const stockMap = condition === 'used' ? product?.used_stocks : product?.new_stocks;
    const serialMap = condition === 'used' ? product?.used_serials : product?.new_serials;
    const stock = Number(stockMap?.[warehouseId] || 0), availableSerials = serialMap?.[warehouseId] || [], tracksSerials = Boolean(product?.track_serials);
    row.querySelector('.item-availability').textContent = product ? `Available ${condition}: ${stock}` : 'Select product';
    quantityInput.max = stock || '';
    quantityInput.readOnly = tracksSerials;
    row.querySelector('.issue-item-serials').hidden = !tracksSerials;
    row.querySelector('.item-serialless-wrap').hidden = !tracksSerials;
    const selected = expandedSerials(serialInput.value), options = row.querySelector('.asset-serial-options');
    options.innerHTML = '';
    if (tracksSerials) {
        availableSerials.forEach(serial => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'asset-serial-option' + (selected.includes(serial) ? ' is-selected' : ''); button.textContent = serial;
            button.addEventListener('click', () => { const current = expandedSerials(serialInput.value); serialInput.value = current.includes(serial) ? current.filter(item => item !== serial).join(', ') : [...current, serial].join(', '); syncIssueRow(row); });
            options.appendChild(button);
        });
        quantityInput.value = selected.length + (parseInt(seriallessInput.value || '0', 10) || 0) || '';
    }
    row.querySelector('.item-total').value = ((parseFloat(quantityInput.value || '0') || 0) * (parseFloat(row.querySelector('.item-unit-price').value || '0') || 0)).toFixed(2);
    syncIssueSummary();
}

function bindIssueRow(row) {
    ['.item-condition','.item-warehouse'].forEach(selector => row.querySelector(selector).addEventListener('change', () => syncIssueRow(row, true)));
    ['.item-serials','.item-serialless','.item-quantity','.item-unit-price'].forEach(selector => row.querySelector(selector).addEventListener('input', () => syncIssueRow(row)));
    row.querySelector('.item-product-search').addEventListener('input', () => { row.querySelector('.item-product-id').value = ''; syncIssueRow(row, true); renderProductSuggestions(row); });
    row.querySelector('.item-product-search').addEventListener('focus', () => renderProductSuggestions(row));
    row.querySelector('.remove-issue-item').addEventListener('click', () => { if (itemsContainer.children.length > 1) row.remove(); reindexIssueRows(); });
}

function reindexIssueRows() {
    [...itemsContainer.children].forEach((row, index) => {
        row.querySelector('.item-number').textContent = `Item ${index + 1}`;
        row.querySelector('.item-product-id').name = `items[${index}][product_id]`;
        row.querySelector('.item-product-search').name = `items[${index}][product_name]`;
        row.querySelector('.item-condition').name = `items[${index}][source_condition]`;
        row.querySelector('.item-warehouse').name = `items[${index}][warehouse_id]`;
        row.querySelector('.item-quantity').name = `items[${index}][quantity]`;
        row.querySelector('.item-unit-price').name = `items[${index}][unit_price]`;
        row.querySelector('.item-total').name = `items[${index}][total]`;
        row.querySelector('.item-serials').name = `items[${index}][serial_numbers]`;
        row.querySelector('.item-serialless').name = `items[${index}][serialless_quantity]`;
        row.querySelector('.remove-issue-item').hidden = itemsContainer.children.length === 1;
    });
    syncIssueSummary();
}

function syncIssueSummary() {
    document.getElementById('summaryRows').textContent = itemsContainer.children.length;
    document.getElementById('summaryUnits').textContent = [...itemsContainer.querySelectorAll('.item-quantity')].reduce((sum,input)=>sum+(parseInt(input.value||'0',10)||0),0);
    document.getElementById('summaryValue').textContent = [...itemsContainer.querySelectorAll('.item-total')].reduce((sum,input)=>sum+(parseFloat(input.value||'0')||0),0).toFixed(2);
    document.getElementById('summaryEmployee').textContent = document.getElementById('issueEmployee').selectedOptions[0]?.textContent || 'Not selected';
}

document.getElementById('addIssueItem').addEventListener('click', () => { const count = Math.min(20, Math.max(1, parseInt(document.getElementById('addItemCount').value || '1', 10))); for (let i=0;i<count;i++) addIssueRow({warehouse_id:defaultWarehouseId,source_condition:'new',serialless_quantity:0}); });
document.getElementById('issueEmployee').addEventListener('change', syncIssueSummary);
document.getElementById('assetIssueForm').addEventListener('submit', reindexIssueRows);
document.addEventListener('click', event => { if (!event.target.closest('.product-picker')) document.querySelectorAll('.issue-item').forEach(hideProductSuggestions); });
initialIssueItems.forEach(item => addIssueRow(item));
</script>
@endsection
