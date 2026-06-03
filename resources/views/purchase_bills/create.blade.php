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
            <select name="party_id">
                <option value="">No vendor selected</option>
                @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((int) old('party_id') === $vendor->id)>{{ $vendor->name }} - {{ $vendor->phone }}</option>
                @endforeach
            </select>
            <span class="muted">Create/edit a party as Vendor from Parties first if missing.</span>
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
                        <select name="items[{{ $index }}][product_id]" required>
                            <option value="">Select product</option>
                            @foreach ($products as $product)
                                <option
                                    value="{{ $product->id }}"
                                    data-brand="{{ $product->brand }}"
                                    data-category-ids="{{ implode(',', $product->categoryIdPath()) }}"
                                    data-unit-price="{{ $product->purchase_price }}"
                                    data-barcode="{{ $product->barcode }}"
                                    data-track-serials="{{ $product->track_serial_numbers ? '1' : '0' }}"
                                    data-warranty-days="{{ $product->warranty_days }}"
                                    @selected((int) ($item['product_id'] ?? 0) === $product->id)
                                >
                                    {{ $product->name }} - {{ $product->sku }}{{ $product->barcode ? ' - '.$product->barcode : '' }}{{ $product->brand ? ' - '.$product->brand : '' }}{{ $product->category ? ' - '.$product->category : '' }}{{ $product->subcategory ? ' / '.$product->subcategory : '' }}
                                </option>
                            @endforeach
                        </select>
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

<template id="purchaseRowTemplate">
    <tr>
        <td>
            <select data-name="product_id" required>
                <option value="">Select product</option>
                @foreach ($products as $product)
                    <option
                        value="{{ $product->id }}"
                        data-brand="{{ $product->brand }}"
                        data-category-ids="{{ implode(',', $product->categoryIdPath()) }}"
                        data-unit-price="{{ $product->purchase_price }}"
                        data-barcode="{{ $product->barcode }}"
                        data-track-serials="{{ $product->track_serial_numbers ? '1' : '0' }}"
                        data-warranty-days="{{ $product->warranty_days }}"
                    >
                        {{ $product->name }} - {{ $product->sku }}{{ $product->barcode ? ' - '.$product->barcode : '' }}{{ $product->brand ? ' - '.$product->brand : '' }}{{ $product->category ? ' - '.$product->category : '' }}{{ $product->subcategory ? ' / '.$product->subcategory : '' }}
                    </option>
                @endforeach
            </select>
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
const assignNames = row => {
    const index = [...rows.children].indexOf(row);
    row.querySelectorAll('[data-name]').forEach(input => {
        input.name = `items[${index}][${input.dataset.name}]`;
    });
};
const syncProductDefaults = row => {
    const productSelect = row.querySelector('select[name$="[product_id]"], select[data-name="product_id"]');
    const selected = productSelect?.selectedOptions[0];
    const unitPrice = row.querySelector('input[name$="[unit_price]"], input[data-name="unit_price"]');
    const warrantyDays = row.querySelector('input[name$="[warranty_days]"], input[data-name="warranty_days"]');
    const serialNumbers = row.querySelector('textarea[name$="[serial_numbers]"], textarea[data-name="serial_numbers"]');

    if (!selected || !selected.value) {
        if (serialNumbers) {
            serialNumbers.disabled = false;
            serialNumbers.placeholder = 'One serial per line';
        }
        return;
    }

    if (unitPrice && (!unitPrice.value || Number(unitPrice.value) === 0) && selected.dataset.unitPrice) {
        unitPrice.value = selected.dataset.unitPrice;
    }

    if (warrantyDays && !warrantyDays.value && selected.dataset.warrantyDays) {
        warrantyDays.value = selected.dataset.warrantyDays;
    }

    if (serialNumbers) {
        const tracksSerials = selected.dataset.trackSerials === '1';
        serialNumbers.disabled = !tracksSerials;
        serialNumbers.placeholder = tracksSerials ? '1001-1010, 1020-1030' : 'Serial not tracked for this product';
        if (!tracksSerials) {
            serialNumbers.value = '';
        }
    }
};
const applyProductFilters = () => {
    const brand = brandFilter.value;
    const category = categoryFilter.value;

    rows.querySelectorAll('select[name$="[product_id]"]').forEach(select => {
        let selectedStillVisible = true;

        select.querySelectorAll('option').forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const visible = (!brand || option.dataset.brand === brand)
                && (!category || (option.dataset.categoryIds || '').split(',').includes(category));
            option.hidden = !visible;

            if (option.selected && !visible) {
                selectedStillVisible = false;
            }
        });

        if (!selectedStillVisible) {
            select.value = '';
        }
    });
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
rows.addEventListener('change', event => {
    if (!event.target.matches('select[name$="[product_id]"]')) return;
    syncProductDefaults(event.target.closest('tr'));
});
document.addEventListener('click', event => {
    if (! event.target.closest('[data-remove-row]')) return;
    if (rows.children.length <= 1) return;
    event.target.closest('tr').remove();
    rows.querySelectorAll('tr').forEach(assignNames);
});
applyProductFilters();
</script>
@endsection
