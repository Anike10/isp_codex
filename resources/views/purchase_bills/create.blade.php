@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Purchase Bill</h1><div class="muted">Enter wholesale/vendor bills and add stock with serial and warranty details</div></div>
    <a class="btn light" href="{{ route('purchase-bills.index') }}">Back</a>
</div>

<form method="post" action="{{ route('purchase-bills.store') }}" class="card" id="purchaseBillForm">
    @csrf
    <div class="form-grid">
        <div>
            <label>Vendor Party</label>
            @php($selectedVendor = $vendors->firstWhere('id', (int) old('party_id')))
            <div class="vendor-picker" style="position:relative">
                <input type="hidden" id="party_id" name="party_id" value="{{ old('party_id') }}">
                <input
                    id="party_name"
                    name="party_name"
                    value="{{ old('party_name', $selectedVendor?->name) }}"
                    placeholder="Type vendor name or mobile number"
                    autocomplete="off"
                    data-vendor-search
                >
                <div id="vendorSuggestions" class="vendor-suggestions customer-suggestions"></div>
            </div>
            <span class="muted">Select an existing vendor, or keep a new name to add it automatically.</span>
        </div>
        <div><label>Bill No</label><input name="bill_no" value="{{ old('bill_no', $defaultBillNo) }}" required></div>
        <div><label>Purchase Date</label><input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" required></div>
        <div class="full"><label>Note</label><textarea name="note" rows="2">{{ old('note') }}</textarea></div>
    </div>

    <h2 style="margin-top:18px">Products</h2>
    <div class="form-grid" style="margin-bottom:12px">
        <div>
            <label>Filter Brand</label>
            <select id="purchaseBrandFilter">
                <option value="">All brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Filter Category</label>
            <input type="hidden" id="purchaseCategoryFilter">
            <div class="form-grid" id="purchaseCategoryCascade"></div>
            <span class="muted">Sub category lists appear automatically.</span>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th style="width:100px">Qty</th>
                <th style="width:130px">Unit Price</th>
                <th style="width:130px">Warranty Days</th>
                <th>Serial Numbers</th>
                <th style="width:70px"></th>
            </tr>
        </thead>
        <tbody id="purchaseRows">
            @php($oldItems = old('items', [['product_id' => '', 'quantity' => 1, 'unit_price' => 0, 'warranty_days' => '', 'serial_numbers' => '']]))
            @foreach ($oldItems as $index => $item)
                <tr>
                    <td>
                        @php($selectedProduct = $products->firstWhere('id', (int) ($item['product_id'] ?? 0)))
                        <div class="product-picker" style="position:relative">
                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] ?? '' }}" data-name="product_id">
                            <input
                                name="items[{{ $index }}][product_name]"
                                value="{{ $item['product_name'] ?? $selectedProduct?->name ?? '' }}"
                                placeholder="Type product name, SKU, barcode, or brand"
                                autocomplete="off"
                                data-name="product_name"
                                data-product-search
                                required
                            >
                            <div class="product-suggestions customer-suggestions"></div>
                            <span class="muted">Select a product, or keep a new name to add it automatically.</span>
                        </div>
                    </td>
                    <td><input type="number" name="items[{{ $index }}][quantity]" min="1" value="{{ $item['quantity'] ?? 1 }}" required></td>
                    <td><input type="number" name="items[{{ $index }}][unit_price]" min="0" step="0.01" value="{{ $item['unit_price'] ?? 0 }}" required></td>
                    <td><input type="number" name="items[{{ $index }}][warranty_days]" min="0" max="3650" value="{{ $item['warranty_days'] ?? ($item['warranty_months'] ?? '') }}"></td>
                    <td><textarea name="items[{{ $index }}][serial_numbers]" rows="2" placeholder="1001-1010, 1020-1030">{{ $item['serial_numbers'] ?? '' }}</textarea></td>
                    <td><button class="btn light" type="button" data-remove-row>Remove</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="actions" style="margin-top:14px">
        <button class="btn light" type="button" id="addPurchaseRow">Add Product Row</button>
        <button class="btn" type="submit">Save Purchase Bill</button>
    </div>
</form>

<style>
    .product-suggestions,
    .vendor-suggestions {
        display: none;
        position: absolute;
        z-index: 30;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        max-height: 260px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #c8d2df;
        border-radius: 8px;
        box-shadow: 0 14px 30px rgba(23, 32, 51, .14);
    }

    .product-suggestion {
        width: 100%;
        border: 0;
        border-bottom: 1px solid #edf1f5;
        background: #fff;
        padding: 10px 12px;
        text-align: left;
        cursor: pointer;
        font: inherit;
    }

    .product-suggestion:last-child {
        border-bottom: 0;
    }

    .product-suggestion:hover,
    .product-suggestion.is-active {
        background: #edf8f4;
    }

    .product-suggestion strong,
    .product-suggestion span {
        display: block;
    }

    .product-suggestion span {
        margin-top: 3px;
        color: #667085;
        font-size: 12px;
    }
</style>

<template id="purchaseRowTemplate">
    <tr>
        <td>
            <div class="product-picker" style="position:relative">
                <input type="hidden" data-name="product_id">
                <input data-name="product_name" data-product-search placeholder="Type product name, SKU, barcode, or brand" autocomplete="off" required>
                <div class="product-suggestions customer-suggestions"></div>
                <span class="muted">Select a product, or keep a new name to add it automatically.</span>
            </div>
        </td>
        <td><input data-name="quantity" type="number" min="1" value="1" required></td>
        <td><input data-name="unit_price" type="number" min="0" step="0.01" value="0" required></td>
        <td><input data-name="warranty_days" type="number" min="0" max="3650"></td>
        <td><textarea data-name="serial_numbers" rows="2" placeholder="1001-1010, 1020-1030"></textarea></td>
        <td><button class="btn light" type="button" data-remove-row>Remove</button></td>
    </tr>
</template>

<script>
const rows = document.getElementById('purchaseRows');
const template = document.getElementById('purchaseRowTemplate');
const brandFilter = document.getElementById('purchaseBrandFilter');
const categoryFilter = document.getElementById('purchaseCategoryFilter');
const categoryCascade = document.getElementById('purchaseCategoryCascade');
const categoryTree = @json($categoryTree);
const products = @json($productSuggestionData);
const vendors = @json($vendorSuggestionData);
const vendorIdInput = document.getElementById('party_id');
const vendorNameInput = document.getElementById('party_name');
const vendorSuggestions = document.getElementById('vendorSuggestions');
let activeProductRow = null;
let highlightedProductIndex = -1;
let activeProductMatches = [];
let highlightedVendorIndex = -1;
let activeVendorMatches = [];
const assignNames = row => {
    const index = [...rows.children].indexOf(row);
    row.querySelectorAll('[data-name]').forEach(input => {
        input.name = `items[${index}][${input.dataset.name}]`;
    });
};
const syncProductDefaults = row => {
    const productId = row.querySelector('input[name$="[product_id]"], input[data-name="product_id"]')?.value;
    const selected = products.find(product => String(product.id) === String(productId));
    const unitPrice = row.querySelector('input[name$="[unit_price]"], input[data-name="unit_price"]');
    const warrantyDays = row.querySelector('input[name$="[warranty_days]"], input[data-name="warranty_days"]');
    const serialNumbers = row.querySelector('textarea[name$="[serial_numbers]"], textarea[data-name="serial_numbers"]');

    if (!selected) {
        if (serialNumbers) {
            serialNumbers.disabled = false;
            serialNumbers.placeholder = 'Serials allowed for new products';
        }
        return;
    }

    if (unitPrice && (!unitPrice.value || Number(unitPrice.value) === 0) && selected.unit_price) {
        unitPrice.value = selected.unit_price;
    }

    if (warrantyDays && !warrantyDays.value && selected.warranty_days) {
        warrantyDays.value = selected.warranty_days;
    }

    if (serialNumbers) {
        serialNumbers.disabled = !selected.track_serials;
        serialNumbers.placeholder = selected.track_serials ? '1001-1010, 1020-1030' : 'Serial not tracked for this product';
        if (!selected.track_serials) {
            serialNumbers.value = '';
        }
    }
};
const productAllowedByFilters = product => {
    const brand = brandFilter.value;
    const category = categoryFilter.value;

    return (!brand || product.brand === brand)
        && (!category || (product.category_ids || []).map(String).includes(String(category)));
};
const applyProductFilters = () => hideProductSuggestions();
const escapeHtml = value => String(value || '').replace(/[&<>"']/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[char]));
const productLabel = product => [
    product.sku,
    product.barcode,
    product.brand,
    product.category,
    product.subcategory,
].filter(Boolean).join(' - ');
const productSearchText = product => [
    product.name,
    product.sku,
    product.barcode,
    product.brand,
    product.category,
    product.subcategory,
].filter(Boolean).join(' ').toLowerCase();
const vendorLabel = vendor => [
    vendor.phone || 'No mobile',
    vendor.connection_id || null,
    'Vendor',
].filter(Boolean).join(' - ');
const vendorSearchText = vendor => [
    vendor.name,
    vendor.phone,
    vendor.connection_id,
].filter(Boolean).join(' ').toLowerCase();
const hideVendorSuggestions = () => {
    vendorSuggestions.style.display = 'none';
    vendorSuggestions.innerHTML = '';
    activeVendorMatches = [];
    highlightedVendorIndex = -1;
};
const selectVendor = vendor => {
    vendorIdInput.value = vendor.id;
    vendorNameInput.value = vendor.name;
    hideVendorSuggestions();
};
const keepNewVendorName = () => {
    vendorIdInput.value = '';
    hideVendorSuggestions();
};
const refreshVendorHighlight = () => {
    vendorSuggestions.querySelectorAll('.product-suggestion').forEach((button, index) => {
        button.classList.toggle('is-active', index === highlightedVendorIndex);
    });
};
const renderVendorSuggestions = () => {
    const query = vendorNameInput.value.trim().toLowerCase();

    vendorSuggestions.innerHTML = '';
    vendorIdInput.value = '';

    if (query.length < 1) {
        hideVendorSuggestions();
        return;
    }

    activeVendorMatches = vendors
        .filter(vendor => vendorSearchText(vendor).includes(query))
        .slice(0, 10);

    activeVendorMatches.forEach((vendor, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'product-suggestion';
        button.innerHTML = `<strong>${escapeHtml(vendor.name)}</strong><span>${escapeHtml(vendorLabel(vendor))}</span>`;
        button.addEventListener('mouseenter', () => {
            highlightedVendorIndex = index;
            refreshVendorHighlight();
        });
        button.addEventListener('click', () => selectVendor(vendor));
        vendorSuggestions.appendChild(button);
    });

    const exactMatch = activeVendorMatches.some(vendor => vendor.name.toLowerCase() === query);
    if (!exactMatch) {
        const addIndex = activeVendorMatches.length;
        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'product-suggestion';
        addButton.innerHTML = `<strong>Add new vendor: ${escapeHtml(vendorNameInput.value.trim())}</strong><span>This vendor party will be created when the purchase bill is saved.</span>`;
        addButton.addEventListener('mouseenter', () => {
            highlightedVendorIndex = addIndex;
            refreshVendorHighlight();
        });
        addButton.addEventListener('click', keepNewVendorName);
        vendorSuggestions.appendChild(addButton);
    }

    highlightedVendorIndex = 0;
    vendorSuggestions.style.display = 'block';
    refreshVendorHighlight();
};
const hideProductSuggestions = () => {
    document.querySelectorAll('.product-picker .product-suggestions').forEach(suggestions => {
        suggestions.style.display = 'none';
        suggestions.innerHTML = '';
    });
    activeProductRow = null;
    activeProductMatches = [];
    highlightedProductIndex = -1;
};
const selectProduct = (row, product) => {
    row.querySelector('input[name$="[product_id]"], input[data-name="product_id"]').value = product.id;
    row.querySelector('input[name$="[product_name]"], input[data-name="product_name"]').value = product.name;
    hideProductSuggestions();
    syncProductDefaults(row);
};
const keepNewProductName = row => {
    row.querySelector('input[name$="[product_id]"], input[data-name="product_id"]').value = '';
    hideProductSuggestions();
    syncProductDefaults(row);
};
const refreshProductHighlight = row => {
    row.querySelectorAll('.product-suggestion').forEach((button, index) => {
        button.classList.toggle('is-active', index === highlightedProductIndex);
    });
};
const renderProductSuggestions = row => {
    const input = row.querySelector('input[name$="[product_name]"], input[data-name="product_name"]');
    const suggestions = row.querySelector('.product-suggestions');
    const query = input.value.trim().toLowerCase();

    suggestions.innerHTML = '';
    activeProductRow = row;

    if (query.length < 1) {
        hideProductSuggestions();
        return;
    }

    activeProductMatches = products
        .filter(product => productAllowedByFilters(product))
        .filter(product => productSearchText(product).includes(query))
        .slice(0, 10);

    activeProductMatches.forEach((product, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'product-suggestion';
        button.innerHTML = `<strong>${escapeHtml(product.name)}</strong><span>${escapeHtml(productLabel(product) || 'No SKU')}</span>`;
        button.addEventListener('mouseenter', () => {
            highlightedProductIndex = index;
            refreshProductHighlight(row);
        });
        button.addEventListener('click', () => selectProduct(row, product));
        suggestions.appendChild(button);
    });

    const exactMatch = activeProductMatches.some(product => product.name.toLowerCase() === query);
    if (!exactMatch) {
        const addIndex = activeProductMatches.length;
        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'product-suggestion';
        addButton.innerHTML = `<strong>Add new product: ${escapeHtml(input.value.trim())}</strong><span>This product will be created when the purchase bill is saved.</span>`;
        addButton.addEventListener('mouseenter', () => {
            highlightedProductIndex = addIndex;
            refreshProductHighlight(row);
        });
        addButton.addEventListener('click', () => keepNewProductName(row));
        suggestions.appendChild(addButton);
    }

    highlightedProductIndex = 0;
    suggestions.style.display = 'block';
    refreshProductHighlight(row);
};
const renderCategoryFilterLevel = (nodes, level = 0) => {
    if (!nodes || nodes.length === 0) return;

    const wrapper = document.createElement('div');
    const label = document.createElement('label');
    label.textContent = level === 0 ? 'Category' : `Sub Category ${level}`;

    const select = document.createElement('select');
    select.innerHTML = '<option value="">All</option>';
    nodes.forEach(node => {
        const option = document.createElement('option');
        option.value = node.id;
        option.textContent = node.name;
        select.appendChild(option);
    });

    select.addEventListener('change', () => {
        [...categoryCascade.children].slice(level + 1).forEach(child => child.remove());
        categoryFilter.value = select.value || '';
        const selected = nodes.find(node => String(node.id) === select.value);
        if (selected && selected.children && selected.children.length > 0) {
            renderCategoryFilterLevel(selected.children, level + 1);
        }
        applyProductFilters();
    });

    wrapper.appendChild(label);
    wrapper.appendChild(select);
    categoryCascade.appendChild(wrapper);
};
document.getElementById('addPurchaseRow').addEventListener('click', () => {
    const row = template.content.firstElementChild.cloneNode(true);
    rows.appendChild(row);
    assignNames(row);
    syncProductDefaults(row);
    applyProductFilters();
});
brandFilter.addEventListener('change', applyProductFilters);
renderCategoryFilterLevel(categoryTree);
rows.querySelectorAll('tr').forEach(syncProductDefaults);
rows.addEventListener('input', event => {
    if (!event.target.matches('[data-product-search]')) return;
    const row = event.target.closest('tr');
    row.querySelector('input[name$="[product_id]"], input[data-name="product_id"]').value = '';
    syncProductDefaults(row);
    renderProductSuggestions(row);
});
rows.addEventListener('keydown', event => {
    if (!event.target.matches('[data-product-search]') || !activeProductRow) return;
    const totalOptions = activeProductRow.querySelectorAll('.product-suggestion').length;
    if (totalOptions === 0) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedProductIndex = (highlightedProductIndex + 1) % totalOptions;
        refreshProductHighlight(activeProductRow);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedProductIndex = (highlightedProductIndex - 1 + totalOptions) % totalOptions;
        refreshProductHighlight(activeProductRow);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const selectedProduct = activeProductMatches[highlightedProductIndex];
        if (selectedProduct) {
            selectProduct(activeProductRow, selectedProduct);
        } else {
            keepNewProductName(activeProductRow);
        }
    } else if (event.key === 'Escape') {
        hideProductSuggestions();
    }
});
rows.addEventListener('focusin', event => {
    if (!event.target.matches('[data-product-search]')) return;
    if (event.target.value.trim()) {
        renderProductSuggestions(event.target.closest('tr'));
    }
});
vendorNameInput.addEventListener('input', renderVendorSuggestions);
vendorNameInput.addEventListener('focus', () => {
    if (vendorNameInput.value.trim()) {
        renderVendorSuggestions();
    }
});
vendorNameInput.addEventListener('keydown', event => {
    const totalOptions = vendorSuggestions.querySelectorAll('.product-suggestion').length;
    if (vendorSuggestions.style.display !== 'block' || totalOptions === 0) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedVendorIndex = (highlightedVendorIndex + 1) % totalOptions;
        refreshVendorHighlight();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedVendorIndex = (highlightedVendorIndex - 1 + totalOptions) % totalOptions;
        refreshVendorHighlight();
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const selectedVendor = activeVendorMatches[highlightedVendorIndex];
        if (selectedVendor) {
            selectVendor(selectedVendor);
        } else {
            keepNewVendorName();
        }
    } else if (event.key === 'Escape') {
        hideVendorSuggestions();
    }
});
document.addEventListener('click', event => {
    if (!event.target.closest('.product-picker')) {
        hideProductSuggestions();
    }

    if (!event.target.closest('.vendor-picker')) {
        hideVendorSuggestions();
    }

    if (! event.target.closest('[data-remove-row]')) return;
    if (rows.children.length <= 1) return;
    event.target.closest('tr').remove();
    rows.querySelectorAll('tr').forEach(assignNames);
});
applyProductFilters();
</script>
@endsection
