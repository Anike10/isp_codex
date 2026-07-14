@extends('layouts.app')

@section('content')
@php
    $isEditing = isset($product);
    $formAction = $isEditing ? route('products.update', $product) : route('products.store');
    $selectedProductType = old('product_type', $product->product_type ?? 'stock');
    $selectedCategoryId = old('product_category_id', $product->product_category_id ?? '');
    $trackInventoryDefault = old('track_inventory', $isEditing ? (string) (int) $product->track_inventory : '1');
    $trackSerialsDefault = old('track_serial_numbers', $isEditing ? (string) (int) $product->track_serial_numbers : null);
    $needsSerialConversionData = $isEditing && $product->track_inventory && ! $product->track_serial_numbers && (int) $product->stock_quantity > 0;
@endphp
<style>
    .product-option {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .product-option-heading {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        font-weight: 700;
    }

    .product-option-heading input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin: 0;
        flex: 0 0 auto;
    }
</style>

<div class="topbar">
    <div><h1>{{ $isEditing ? 'Edit Product' : 'Add Product' }}</h1><div class="muted">{{ $isEditing ? 'Update product details without changing stock ledger' : 'Create stock item for computer shop or ISP inventory' }}</div></div>
    <a class="btn light" href="{{ $isEditing ? route('products.show', $product) : route('products.index') }}">Back</a>
</div>

<form method="post" action="{{ $formAction }}" class="card form-grid">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif
    <div><label>Name</label><input name="name" value="{{ old('name', $product->name ?? '') }}" required></div>
    <div><label>SKU</label><input name="sku" value="{{ old('sku', $product->sku ?? '') }}" required></div>
    <div><label>Barcode</label><input name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" placeholder="Optional barcode"></div>
    <div>
        <label>Product Type</label>
        <select name="product_type" id="product_type" required>
            <option value="stock" @selected($selectedProductType === 'stock')>Stock Product</option>
            <option value="serial_stock" @selected($selectedProductType === 'serial_stock')>Serial Stock Product</option>
            <option value="consumable" @selected($selectedProductType === 'consumable')>Consumable / Own Use</option>
            <option value="service" @selected($selectedProductType === 'service')>Service Product</option>
            <option value="warranty" @selected($selectedProductType === 'warranty')>Warranty / Repair Service</option>
        </select>
    </div>
    <div>
        <label>Brand</label>
        <input name="brand" value="{{ old('brand', $product->brand ?? '') }}" list="brandOptions" placeholder="TP-Link, MikroTik, Intel">
    </div>
    <div class="full">
        <label>Category</label>
        <input type="hidden" name="product_category_id" id="product_category_id" value="{{ $selectedCategoryId }}">
        <div class="form-grid" id="categoryCascade"></div>
        <span class="muted">Select a category. If it has sub categories, the next list will appear automatically.</span>
    </div>
    <div><label>Purchase Price</label><input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" required></div>
    <div><label>Sale Price</label><input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? 0) }}" required></div>
    <div class="full product-option">
        <input type="hidden" name="track_inventory" value="0">
        <label class="product-option-heading">Track inventory stock <input type="checkbox" name="track_inventory" value="1" @checked($trackInventoryDefault)></label>
        <span class="muted">Uncheck this for services or non-stock purchases that should not affect inventory.</span>
    </div>
    <div class="product-option" data-inventory-field>
        <input type="hidden" name="track_serial_numbers" value="0">
        <label class="product-option-heading">Track serial numbers <input type="checkbox" name="track_serial_numbers" value="1" @checked($trackSerialsDefault)></label>
        <span class="muted">Enable this for routers, ONUs, devices, or anything with individual serial numbers.</span>
    </div>
    @if($needsSerialConversionData)
        <div class="full" id="serialConversionFields" data-inventory-field style="padding:14px;border:1px solid var(--line);border-radius:8px;background:#fbfcfe">
            <h2 style="margin-bottom:8px">Existing Stock Serial Split</h2>
            <div class="muted" style="margin-bottom:12px">Current stock is {{ $product->stock_quantity }}. To make this product serial-tracked, enter serials for the units that have serial numbers and Serial-less Qty for the rest.</div>
            <div class="form-grid">
                <div class="full">
                    <label>Existing Serials</label>
                    <textarea name="serial_conversion_serial_numbers" rows="3" placeholder="1001-1010, ONU020">{{ old('serial_conversion_serial_numbers') }}</textarea>
                </div>
                <div>
                    <label>Serial-less Qty</label>
                    <input type="number" name="serial_conversion_serialless_quantity" min="0" max="{{ $product->stock_quantity }}" value="{{ old('serial_conversion_serialless_quantity', 0) }}">
                </div>
            </div>
            <span class="muted">Serial count plus Serial-less Qty must equal {{ $product->stock_quantity }}.</span>
        </div>
    @endif
    <div data-inventory-field><label>Default Warranty Days</label><input type="number" name="warranty_days" min="0" max="3650" value="{{ old('warranty_days', $product->warranty_days ?? '') }}" placeholder="Example: 365"></div>
    <div data-service-field><label>Service Guarantee Days</label><input type="number" name="service_guarantee_days" min="0" max="3650" value="{{ old('service_guarantee_days', $product->service_guarantee_days ?? '') }}" placeholder="Example: 30"></div>
    @if($isEditing)
        <div data-inventory-field><label>Current Stock</label><input type="number" value="{{ $product->stock_quantity }}" disabled><span class="muted">Use Move Stock or Purchase Bill to change stock.</span></div>
    @else
        <div data-inventory-field><label>Opening Stock</label><input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" required></div>
    @endif
    <div data-inventory-field><label>Low Stock Alert</label><input type="number" name="low_stock_alert" value="{{ old('low_stock_alert', $product->low_stock_alert ?? 5) }}" required></div>
    <div class="full"><button class="btn" type="submit">{{ $isEditing ? 'Update Product' : 'Save Product' }}</button></div>
</form>
<datalist id="brandOptions">
    @foreach ($brands as $brand)
        <option value="{{ $brand }}">
    @endforeach
</datalist>
<script>
const categoryTree = @json($categoryTree);
const selectedCategoryInput = document.getElementById('product_category_id');
const categoryCascade = document.getElementById('categoryCascade');
const trackInventory = document.querySelector('input[type="checkbox"][name="track_inventory"]');
const trackSerials = document.querySelector('input[type="checkbox"][name="track_serial_numbers"]');
const productType = document.getElementById('product_type');
const inventoryFields = document.querySelectorAll('[data-inventory-field]');
const serviceFields = document.querySelectorAll('[data-service-field]');
const originalTracksSerials = @json($isEditing ? (bool) $product->track_serial_numbers : false);
const serialConversionFields = document.getElementById('serialConversionFields');

function syncInventoryFields() {
    const serviceType = ['service', 'warranty'].includes(productType.value);
    if (serviceType) {
        trackInventory.checked = false;
        trackSerials.checked = false;
    } else if (productType.value === 'serial_stock') {
        trackInventory.checked = true;
        trackSerials.checked = true;
    }

    inventoryFields.forEach(field => {
        field.style.display = trackInventory.checked ? '' : 'none';
        field.querySelectorAll('input').forEach(input => {
            input.disabled = !trackInventory.checked;
        });
    });
    serviceFields.forEach(field => {
        field.style.display = serviceType ? '' : 'none';
        field.querySelectorAll('input').forEach(input => {
            input.disabled = !serviceType;
        });
    });

    if (serialConversionFields) {
        const shouldShow = trackInventory.checked && trackSerials.checked && !originalTracksSerials;
        serialConversionFields.style.display = shouldShow ? '' : 'none';
        serialConversionFields.querySelectorAll('input, textarea').forEach(input => {
            input.disabled = !shouldShow;
        });
    }
}

function renderCategoryLevel(nodes, level = 0) {
    if (!nodes || nodes.length === 0) return;

    const wrapper = document.createElement('div');
    const label = document.createElement('label');
    label.textContent = level === 0 ? 'Category' : `Sub Category ${level}`;

    const select = document.createElement('select');
    select.innerHTML = '<option value="">Select</option>';
    nodes.forEach(node => {
        const option = document.createElement('option');
        option.value = node.id;
        option.textContent = node.name;
        select.appendChild(option);
    });

    select.addEventListener('change', () => {
        [...categoryCascade.children].slice(level + 1).forEach(child => child.remove());
        const selected = nodes.find(node => String(node.id) === select.value);
        selectedCategoryInput.value = select.value || '';
        if (selected && selected.children && selected.children.length > 0) {
            renderCategoryLevel(selected.children, level + 1);
        }
    });

    wrapper.appendChild(label);
    wrapper.appendChild(select);
    categoryCascade.appendChild(wrapper);
}

renderCategoryLevel(categoryTree);
function findCategoryPath(nodes, targetId, path = []) {
    for (const node of nodes || []) {
        const nextPath = [...path, node];
        if (String(node.id) === String(targetId)) return nextPath;
        const found = findCategoryPath(node.children, targetId, nextPath);
        if (found) return found;
    }
    return null;
}
const selectedPath = findCategoryPath(categoryTree, selectedCategoryInput.value);
if (selectedPath) {
    categoryCascade.innerHTML = '';
    let nodes = categoryTree;
    selectedPath.forEach((node, level) => {
        renderCategoryLevel(nodes, level);
        const select = categoryCascade.children[level].querySelector('select');
        select.value = node.id;
        nodes = node.children || [];
    });
    if (nodes.length > 0) renderCategoryLevel(nodes, selectedPath.length);
}
trackInventory.addEventListener('change', syncInventoryFields);
trackSerials.addEventListener('change', syncInventoryFields);
productType.addEventListener('change', syncInventoryFields);
syncInventoryFields();
</script>
@endsection
