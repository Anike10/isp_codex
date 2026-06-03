@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Product</h1><div class="muted">Create stock item for computer shop or ISP inventory</div></div>
    <a class="btn light" href="{{ route('products.index') }}">Back</a>
</div>

<form method="post" action="{{ route('products.store') }}" class="card form-grid">
    @csrf
    <div><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
    <div><label>SKU</label><input name="sku" value="{{ old('sku') }}" required></div>
    <div><label>Barcode</label><input name="barcode" value="{{ old('barcode') }}" placeholder="Optional barcode"></div>
    <div>
        <label>Product Type</label>
        <select name="product_type" id="product_type" required>
            <option value="stock" @selected(old('product_type', 'stock') === 'stock')>Stock Product</option>
            <option value="serial_stock" @selected(old('product_type') === 'serial_stock')>Serial Stock Product</option>
            <option value="consumable" @selected(old('product_type') === 'consumable')>Consumable / Own Use</option>
            <option value="service" @selected(old('product_type') === 'service')>Service Product</option>
            <option value="warranty" @selected(old('product_type') === 'warranty')>Warranty / Repair Service</option>
        </select>
    </div>
    <div>
        <label>Brand</label>
        <input name="brand" value="{{ old('brand') }}" list="brandOptions" placeholder="TP-Link, MikroTik, Intel">
    </div>
    <div class="full">
        <label>Category</label>
        <input type="hidden" name="product_category_id" id="product_category_id" value="{{ old('product_category_id') }}">
        <div class="form-grid" id="categoryCascade"></div>
        <span class="muted">Select a category. If it has sub categories, the next list will appear automatically.</span>
    </div>
    <div><label>Purchase Price</label><input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', 0) }}" required></div>
    <div><label>Sale Price</label><input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', 0) }}" required></div>
    <div class="full">
        <input type="hidden" name="track_inventory" value="0">
        <label><input type="checkbox" name="track_inventory" value="1" @checked(old('track_inventory', '1'))> Track inventory stock</label>
        <span class="muted">Uncheck this for services or non-stock purchases that should not affect inventory.</span>
    </div>
    <div data-inventory-field>
        <input type="hidden" name="track_serial_numbers" value="0">
        <label><input type="checkbox" name="track_serial_numbers" value="1" @checked(old('track_serial_numbers'))> Track serial numbers</label>
        <span class="muted">Enable this for routers, ONUs, devices, or anything with individual serial numbers.</span>
    </div>
    <div data-inventory-field><label>Default Warranty Days</label><input type="number" name="warranty_days" min="0" max="3650" value="{{ old('warranty_days') }}" placeholder="Example: 365"></div>
    <div data-service-field><label>Service Guarantee Days</label><input type="number" name="service_guarantee_days" min="0" max="3650" value="{{ old('service_guarantee_days') }}" placeholder="Example: 30"></div>
    <div data-inventory-field><label>Opening Stock</label><input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" required></div>
    <div data-inventory-field><label>Low Stock Alert</label><input type="number" name="low_stock_alert" value="{{ old('low_stock_alert', 5) }}" required></div>
    <div class="full"><button class="btn" type="submit">Save Product</button></div>
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
productType.addEventListener('change', syncInventoryFields);
syncInventoryFields();
</script>
@endsection
