@extends('layouts.app')

@section('content')
@php
    $isEdit = isset($invoice);
    $invoiceItems = old('items', $isEdit
        ? $invoice->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'serial_numbers' => $item->serial_numbers,
        ])->toArray()
        : [
        [
            'product_id' => '',
            'product_name' => '',
            'quantity' => 1,
            'unit_price' => '',
            'serial_numbers' => '',
        ],
    ]);
    $selectedCustomer = old('customer_id')
        ? $customers->firstWhere('id', (int) old('customer_id'))
        : ($isEdit ? $invoice->customer : null);
    $selectedCustomerId = old('customer_id', $selectedCustomer?->id);
@endphp

<style>
    .invoice-page {
        display: grid;
        gap: 18px;
    }

    .invoice-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: end;
        padding: 22px;
        border: 1px solid #d8dee9;
        border-radius: 8px;
        background:
            linear-gradient(135deg, rgba(17, 97, 73, .10), rgba(29, 118, 201, .08)),
            #ffffff;
    }

    .invoice-hero h1 {
        font-size: 30px;
        letter-spacing: 0;
    }

    .invoice-hero .muted {
        max-width: 650px;
        margin-top: 8px;
        line-height: 1.5;
    }

    .invoice-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 18px;
        align-items: start;
    }

    .invoice-panel,
    .invoice-summary {
        background: #ffffff;
        border: 1px solid #d8dee9;
        border-radius: 8px;
    }

    .invoice-panel {
        overflow: hidden;
    }

    .section-head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        padding: 18px 20px;
        border-bottom: 1px solid #e6ebf2;
        background: #fbfcfe;
    }

    .section-head h2 {
        margin: 0;
        font-size: 18px;
    }

    .section-head p {
        margin: 5px 0 0;
        color: #667085;
        line-height: 1.4;
    }

    .section-body {
        padding: 20px;
    }

    .invoice-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .field-note {
        display: block;
        margin-top: 6px;
        color: #667085;
        font-size: 12px;
        line-height: 1.35;
    }

    .amount-mode {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 120px;
        gap: 8px;
    }

    .items-list {
        display: grid;
        gap: 12px;
    }

    .item-row {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) 96px 130px 130px 44px;
        gap: 12px;
        align-items: end;
        padding: 14px;
        border: 1px solid #e1e7ef;
        border-radius: 8px;
        background: #ffffff;
    }

    .product-picker {
        position: relative;
    }

    .item-serials {
        grid-column: 1 / -2;
    }

    .serial-options {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .serial-option {
        border: 1px solid #c8d2df;
        border-radius: 6px;
        background: #ffffff;
        color: #172033;
        padding: 5px 8px;
        cursor: pointer;
        font: inherit;
        font-size: 12px;
    }

    .serial-option.is-selected {
        border-color: #116149;
        background: #edf8f4;
        color: #0f513e;
    }

    .serial-option:focus {
        outline: 2px solid #116149;
        outline-offset: 2px;
    }

    .item-serials.is-hidden {
        display: none;
    }

    .item-row:hover {
        border-color: #b7c7d9;
        box-shadow: 0 8px 18px rgba(23, 32, 51, .06);
    }

    .remove-item {
        width: 44px;
        justify-content: center;
        padding: 10px;
        background: #fff0f0;
        color: #b42318;
    }

    .add-item-bar {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        margin-top: 14px;
        padding-top: 16px;
        border-top: 1px dashed #c8d2df;
    }

    .invoice-summary {
        position: sticky;
        top: 18px;
        padding: 18px;
    }

    .summary-title {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        margin-bottom: 16px;
    }

    .summary-title h2 {
        margin: 0;
        font-size: 18px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid #edf1f5;
        color: #475467;
    }

    .summary-row strong {
        color: #172033;
    }

    .summary-total {
        margin-top: 14px;
        padding: 16px;
        border-radius: 8px;
        background: #edf8f4;
        color: #0f513e;
    }

    .summary-total span {
        display: block;
        margin-top: 4px;
        font-size: 30px;
        font-weight: 800;
        color: #116149;
    }

    .summary-actions {
        display: grid;
        gap: 10px;
        margin-top: 16px;
    }

    .summary-actions .btn {
        justify-content: center;
        width: 100%;
    }

    .required-mark {
        color: #b42318;
    }

    .customer-picker {
        position: relative;
    }

    .customer-suggestions {
        display: none;
        position: absolute;
        z-index: 20;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        max-height: 240px;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #c8d2df;
        border-radius: 8px;
        box-shadow: 0 14px 30px rgba(23, 32, 51, .14);
    }

    .customer-suggestion {
        width: 100%;
        border: 0;
        border-bottom: 1px solid #edf1f5;
        background: #ffffff;
        padding: 10px 12px;
        text-align: left;
        cursor: pointer;
        font: inherit;
    }

    .customer-suggestion:last-child {
        border-bottom: 0;
    }

    .customer-suggestion:hover {
        background: #edf8f4;
    }

    .customer-suggestion.is-active {
        background: #dff2ea;
        outline: 2px solid #116149;
        outline-offset: -2px;
    }

    .customer-suggestion strong {
        display: block;
        color: #172033;
    }

    .customer-suggestion span {
        display: block;
        margin-top: 3px;
        color: #667085;
        font-size: 12px;
    }

    @media (max-width: 1160px) {
        .invoice-shell {
            grid-template-columns: 1fr;
        }

        .invoice-summary {
            position: static;
        }
    }

    @media (max-width: 780px) {
        .invoice-hero,
        .invoice-form-grid,
        .item-row {
            grid-template-columns: 1fr;
        }

        .invoice-hero {
            align-items: start;
        }

        .add-item-bar {
            align-items: stretch;
            flex-direction: column;
        }

        .remove-item {
            width: 100%;
        }

        .summary-total span {
            font-size: 24px;
        }

        .invoice-page {
            gap: 12px;
        }

        .invoice-hero {
            padding: 14px;
            gap: 10px;
        }

        .invoice-hero h1 {
            font-size: 22px;
        }

        .section-head {
            padding: 13px 14px;
        }

        .section-head h2 {
            font-size: 16px;
        }

        .section-body {
            padding: 14px;
        }

        .invoice-form-grid,
        .items-list {
            gap: 11px;
        }

        .item-row {
            padding: 12px;
            gap: 10px;
        }

        .add-item-bar {
            gap: 10px;
        }

        .invoice-summary {
            padding: 14px;
        }

        .summary-row {
            padding: 9px 0;
        }

        .summary-total {
            padding: 12px;
        }
    }
</style>

<div class="invoice-page">
    <div class="invoice-hero">
        <div>
            <h1>{{ $isEdit ? 'Edit Invoice' : 'Create Invoice' }}</h1>
            <div class="muted">{{ $isEdit ? 'Update this draft invoice before finalizing it.' : 'Create a clean product or one-time charge invoice, add multiple line items, apply discount and VAT, and review the payable amount before saving.' }}</div>
        </div>
        <a class="btn light" href="{{ route('invoices.index') }}">Back to Invoices</a>
    </div>

    <form method="post" action="{{ $isEdit ? route('invoices.update', $invoice) : route('invoices.store') }}" id="invoiceForm">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <div class="invoice-shell">
            <div class="grid">
                <section class="invoice-panel">
                    <div class="section-head">
                        <div>
                            <h2>Invoice Details</h2>
                            <p>Select the party this invoice is for and define the billing period.</p>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="invoice-form-grid">
                            <div class="customer-picker">
                                <label for="customer_name">Party Name <span class="required-mark">*</span></label>
                                <input type="hidden" id="customer_id" name="customer_id" value="{{ $selectedCustomerId }}">
                                <input id="customer_name" name="customer_name" value="{{ old('customer_name', $selectedCustomer?->name) }}" autocomplete="off" placeholder="Type party name or mobile number" required>
                                <div id="customerSuggestions" class="customer-suggestions"></div>
                                <span class="field-note">Start typing a name or mobile number. Select an existing party, or continue with a new party.</span>
                            </div>

                            <div class="customer-picker">
                                <label for="customer_phone">Mobile Number <span class="required-mark">*</span></label>
                                <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $selectedCustomer?->phone) }}" autocomplete="off" placeholder="Party mobile number" required>
                                <div id="customerPhoneSuggestions" class="customer-suggestions"></div>
                                <span class="field-note">Existing party suggestions also show mobile numbers.</span>
                            </div>

                            <div>
                                <label for="billing_month">Billing Month <span class="required-mark">*</span></label>
                                <input id="billing_month" type="month" name="billing_month" value="{{ old('billing_month', $isEdit ? $invoice->billing_month : now()->format('Y-m')) }}" required>
                            </div>

                            <div>
                                <label for="discount">Discount <span class="required-mark">*</span></label>
                                <div class="amount-mode">
                                    <input id="discount" type="number" name="discount" step="0.01" min="0" value="{{ old('discount', $isEdit ? $invoice->discount : '0.00') }}" required>
                                    <select id="discountType" name="discount_type" required>
                                        <option value="amount" @selected(old('discount_type', 'amount') === 'amount')>BDT</option>
                                        <option value="percent" @selected(old('discount_type') === 'percent')>%</option>
                                    </select>
                                </div>
                                <span class="field-note">Use fixed taka amount or percentage of subtotal.</span>
                            </div>

                            <div>
                                <label for="vat">VAT <span class="required-mark">*</span></label>
                                <div class="amount-mode">
                                    <input id="vat" type="number" name="vat" step="0.01" min="0" value="{{ old('vat', $isEdit ? ($invoice->vat ?? '0.00') : '0.00') }}" required>
                                    <select id="vatType" name="vat_type" required>
                                        <option value="amount" @selected(old('vat_type', 'amount') === 'amount')>BDT</option>
                                        <option value="percent" @selected(old('vat_type') === 'percent')>%</option>
                                    </select>
                                </div>
                                <span class="field-note">Use fixed taka amount or percentage after discount.</span>
                            </div>

                            <div>
                                <label for="due_date">Due Date</label>
                                <input id="due_date" type="date" name="due_date" value="{{ old('due_date', $isEdit ? $invoice->due_date?->format('Y-m-d') : null) }}">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="invoice-panel">
                    <div class="section-head">
                        <div>
                            <h2>Line Items</h2>
                            <p>Add products, service charges, installation fees, or other one-time items.</p>
                        </div>
                    </div>
                    <div class="section-body">
                        <div id="itemsContainer" class="items-list">
                            @foreach ($invoiceItems as $index => $item)
                                <div class="item-row">
                                    <div>
                                        <label for="items_{{ $index }}_product_name">Product Name <span class="required-mark">*</span></label>
                                        <div class="product-picker">
                                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] ?? '' }}" data-product-id>
                                            <input id="items_{{ $index }}_product_name" type="text" name="items[{{ $index }}][product_name]" value="{{ $item['product_name'] ?? '' }}" placeholder="Router, cable, setup fee" autocomplete="off" data-product-search required>
                                            <div class="customer-suggestions product-suggestions"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="items_{{ $index }}_quantity">Qty <span class="required-mark">*</span></label>
                                        <input id="items_{{ $index }}_quantity" type="number" name="items[{{ $index }}][quantity]" min="1" value="{{ $item['quantity'] ?? 1 }}" class="quantity" required>
                                    </div>
                                    <div>
                                        <label for="items_{{ $index }}_unit_price">Unit Price <span class="required-mark">*</span></label>
                                        <input id="items_{{ $index }}_unit_price" type="number" name="items[{{ $index }}][unit_price]" step="0.01" min="0" value="{{ $item['unit_price'] ?? '' }}" class="unit-price" required>
                                    </div>
                                    <div>
                                        <label for="items_{{ $index }}_total">Total</label>
                                        <input id="items_{{ $index }}_total" type="number" name="items[{{ $index }}][total]" step="0.01" min="0" class="total" readonly tabindex="-1">
                                    </div>
                                    <div>
                                        <button type="button" class="btn light remove-item" @if (count($invoiceItems) === 1) style="display:none;" @endif aria-label="Remove item">X</button>
                                    </div>
                                    <div class="item-serials">
                                        <label for="items_{{ $index }}_serial_numbers">Serial Numbers</label>
                                        <textarea id="items_{{ $index }}_serial_numbers" name="items[{{ $index }}][serial_numbers]" rows="2" placeholder="Select a serial-tracked product first">{{ $item['serial_numbers'] ?? '' }}</textarea>
                                        <div class="serial-options"></div>
                                        <span class="field-note">Click available serials to add/remove them, or type one per line.</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="add-item-bar">
                            <div class="muted">Each item total is calculated automatically from quantity and unit price.</div>
                            <button type="button" class="btn secondary" id="addItem">Add Item</button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="invoice-summary">
                <div class="summary-title">
                    <h2>Invoice Summary</h2>
                    <span class="badge">{{ $isEdit ? 'Editing Draft' : 'Draft' }}</span>
                </div>

                <div class="summary-row">
                    <span>Items</span>
                    <strong id="itemCount">1</strong>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong>BDT <span id="subtotalAmount">0.00</span></strong>
                </div>
                <div class="summary-row">
                    <span>Discount</span>
                    <strong><span id="discountLabel">BDT</span> <span id="discountAmount">0.00</span></strong>
                </div>
                <div class="summary-row">
                    <span>VAT</span>
                    <strong><span id="vatLabel">BDT</span> <span id="vatAmount">0.00</span></strong>
                </div>

                <div class="summary-total">
                    Payable Amount
                    <span>BDT <span id="grandTotal">0.00</span></span>
                </div>

                <div class="summary-actions">
                    <button class="btn" type="submit">{{ $isEdit ? 'Update Invoice' : 'Create Invoice' }}</button>
                    <a class="btn light" href="{{ $isEdit ? route('invoices.show', $invoice) : route('invoices.index') }}">Cancel</a>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
const customerSearchUrl = @json(route('invoice-customers.search'));
const customerIdInput = document.getElementById('customer_id');
const customerNameInput = document.getElementById('customer_name');
const customerPhoneInput = document.getElementById('customer_phone');
const customerNameSuggestions = document.getElementById('customerSuggestions');
const customerPhoneSuggestions = document.getElementById('customerPhoneSuggestions');
const products = @json($productSuggestionData ?? []);
let customerSearchTimer;
let activeCustomerSuggestions = customerNameSuggestions;
let currentCustomerSuggestions = [];
let highlightedCustomerIndex = -1;
let activeProductRow = null;
let currentProductSuggestions = [];
let highlightedProductIndex = -1;

function clearSelectedCustomer() {
    customerIdInput.value = '';
}

function hideCustomerSuggestions() {
    [customerNameSuggestions, customerPhoneSuggestions].forEach(suggestions => {
        suggestions.style.display = 'none';
        suggestions.innerHTML = '';
    });
    currentCustomerSuggestions = [];
    highlightedCustomerIndex = -1;
}

function selectCustomerSuggestion(customer) {
    customerIdInput.value = customer.id;
    customerNameInput.value = customer.name;
    customerPhoneInput.value = customer.phone || '';
    hideCustomerSuggestions();
}

function refreshHighlightedCustomer() {
    activeCustomerSuggestions.querySelectorAll('.customer-suggestion').forEach((button, index) => {
        const active = index === highlightedCustomerIndex;
        button.classList.toggle('is-active', active);
        if (active) {
            button.scrollIntoView({ block: 'nearest' });
        }
    });
}

function renderCustomerSuggestions(customers) {
    const suggestions = activeCustomerSuggestions;
    suggestions.innerHTML = '';
    currentCustomerSuggestions = customers;
    highlightedCustomerIndex = customers.length > 0 ? 0 : -1;

    if (customers.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'customer-suggestion';
        empty.innerHTML = '<strong>No existing party found</strong><span>This will be added as a new party when the invoice is saved.</span>';
        suggestions.appendChild(empty);
        suggestions.style.display = 'block';
        return;
    }

    customers.forEach((customer, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'customer-suggestion';
        button.innerHTML = `
            <strong>${customer.name}</strong>
            <span>${customer.phone || 'No mobile'} - ${customer.connection_id || 'No connection'} - ${customer.party_type}</span>
        `;
        button.addEventListener('mouseenter', () => {
            highlightedCustomerIndex = index;
            refreshHighlightedCustomer();
        });
        button.addEventListener('click', () => selectCustomerSuggestion(customer));
        suggestions.appendChild(button);
    });

    suggestions.style.display = 'block';
    refreshHighlightedCustomer();
}

function searchCustomers(sourceInput) {
    activeCustomerSuggestions = sourceInput === customerPhoneInput ? customerPhoneSuggestions : customerNameSuggestions;
    (activeCustomerSuggestions === customerNameSuggestions ? customerPhoneSuggestions : customerNameSuggestions).style.display = 'none';

    const query = `${customerNameInput.value} ${customerPhoneInput.value}`.trim();

    clearTimeout(customerSearchTimer);

    if (query.length < 2) {
        hideCustomerSuggestions();
        return;
    }

    customerSearchTimer = setTimeout(() => {
        fetch(`${customerSearchUrl}?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json' },
        })
            .then(response => response.json())
            .then(renderCustomerSuggestions)
            .catch(hideCustomerSuggestions);
    }, 220);
}

customerNameInput.addEventListener('input', () => {
    clearSelectedCustomer();
    searchCustomers(customerNameInput);
});

customerPhoneInput.addEventListener('input', () => {
    clearSelectedCustomer();
    searchCustomers(customerPhoneInput);
});

[customerNameInput, customerPhoneInput].forEach(input => {
    input.addEventListener('focus', () => {
        activeCustomerSuggestions = input === customerPhoneInput ? customerPhoneSuggestions : customerNameSuggestions;
    });

    input.addEventListener('keydown', event => {
        const suggestionsVisible = activeCustomerSuggestions.style.display === 'block';

        if (!suggestionsVisible || currentCustomerSuggestions.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            highlightedCustomerIndex = (highlightedCustomerIndex + 1) % currentCustomerSuggestions.length;
            refreshHighlightedCustomer();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            highlightedCustomerIndex = (highlightedCustomerIndex - 1 + currentCustomerSuggestions.length) % currentCustomerSuggestions.length;
            refreshHighlightedCustomer();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            selectCustomerSuggestion(currentCustomerSuggestions[highlightedCustomerIndex]);
        } else if (event.key === 'Escape') {
            hideCustomerSuggestions();
        }
    });
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.customer-picker')) {
        hideCustomerSuggestions();
    }

    if (!e.target.closest('.product-picker')) {
        hideProductSuggestions();
    }
});

function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
}

function productSearchText(product) {
    return [
        product.name,
        product.sku,
        product.barcode,
        product.brand,
    ].filter(Boolean).join(' ').toLowerCase();
}

function productLabel(product) {
    const stockLabel = product.track_inventory ? `Stock ${product.stock_quantity}` : 'No stock tracking';

    return [
        product.sku,
        product.barcode,
        product.brand,
        stockLabel,
        product.track_serials ? `${product.serials.length} serials` : null,
    ].filter(Boolean).join(' - ');
}

const bengaliDigits = {
    '\u09E6': '0',
    '\u09E7': '1',
    '\u09E8': '2',
    '\u09E9': '3',
    '\u09EA': '4',
    '\u09EB': '5',
    '\u09EC': '6',
    '\u09ED': '7',
    '\u09EE': '8',
    '\u09EF': '9',
};
const asciiDigits = Object.fromEntries(Object.entries(bengaliDigits).map(([key, value]) => [value, key]));

function normalizeSerialDigits(value) {
    return String(value).replace(/[\u09E6-\u09EF]/g, digit => bengaliDigits[digit] || digit);
}

function toBengaliSerialDigits(value) {
    return String(value).replace(/[0-9]/g, digit => asciiDigits[digit] || digit);
}

function expandSerialPart(part) {
    const match = String(part).trim().match(/^([\p{L}_-]*)([0-9\u09E6-\u09EF]+)\s*(?:-|to|থেকে)\s*([\p{L}_-]*)([0-9\u09E6-\u09EF]+)$/iu);

    if (!match) {
        return [part];
    }

    const startPrefix = match[1];
    const endPrefix = match[3] || startPrefix;

    if (startPrefix !== endPrefix) {
        return [part];
    }

    const startText = normalizeSerialDigits(match[2]);
    const endText = normalizeSerialDigits(match[4]);
    const start = Number(startText);
    const end = Number(endText);

    if (!Number.isInteger(start) || !Number.isInteger(end) || end < start || end - start >= 1000) {
        return [part];
    }

    const width = Math.max(startText.length, endText.length);
    const useBengaliDigits = /[\u09E6-\u09EF]/u.test(match[2]);
    const serials = [];

    for (let number = start; number <= end; number++) {
        const serial = String(number).padStart(width, '0');
        serials.push(startPrefix + (useBengaliDigits ? toBengaliSerialDigits(serial) : serial));
    }

    return serials;
}

function selectedSerials(row) {
    const textarea = row.querySelector('[name$="[serial_numbers]"]');

    return (textarea?.value || '')
        .split(/[\r\n,]+/)
        .map(value => value.trim())
        .filter(Boolean);
}

function expandedSelectedSerials(row) {
    return [...new Set(selectedSerials(row).flatMap(expandSerialPart))];
}

function setSelectedSerials(row, serials) {
    row.querySelector('[name$="[serial_numbers]"]').value = [...new Set(serials)].join('\n');
    syncQuantityToSerials(row);
}

function syncQuantityToSerials(row) {
    const serialCount = expandedSelectedSerials(row).length;
    const quantity = row.querySelector('.quantity');

    if (serialCount > 0 && quantity) {
        quantity.value = serialCount;
    }

    updateRowTotal(row);
    updateTotals();
}

function selectedProduct(row) {
    const productId = row.querySelector('[data-product-id]')?.value;

    return products.find(product => String(product.id) === String(productId));
}

function refreshSerialOptions(row, focusSerialNumber = null) {
    const product = selectedProduct(row);
    const serialTextarea = row.querySelector('[name$="[serial_numbers]"]');
    const serialOptions = row.querySelector('.serial-options');
    const serialBlock = row.querySelector('.item-serials');
    let focusButton = null;

    if (!serialTextarea || !serialOptions) return;

    serialOptions.innerHTML = '';

    if (!product || !product.track_serials) {
        serialBlock?.classList.add('is-hidden');
        serialTextarea.disabled = true;
        serialTextarea.value = '';
        return;
    }

    serialBlock?.classList.remove('is-hidden');
    serialTextarea.disabled = false;
    serialTextarea.placeholder = 'Click serials below, or type one per line';
    const chosen = expandedSelectedSerials(row);

    product.serials.forEach(serial => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'serial-option';
        button.setAttribute('aria-pressed', chosen.includes(serial.serial_number) ? 'true' : 'false');
        button.textContent = serial.warranty_until ? `${serial.serial_number} (${serial.warranty_until})` : serial.serial_number;
        button.classList.toggle('is-selected', chosen.includes(serial.serial_number));
        button.addEventListener('click', () => {
            const serials = expandedSelectedSerials(row);
            const nextSerials = serials.includes(serial.serial_number)
                ? serials.filter(value => value !== serial.serial_number)
                : [...serials, serial.serial_number];

            setSelectedSerials(row, nextSerials);
            refreshSerialOptions(row, serial.serial_number);
        });
        serialOptions.appendChild(button);

        if (focusSerialNumber === serial.serial_number) {
            focusButton = button;
        }
    });

    focusButton?.focus();
}

function hideProductSuggestions() {
    document.querySelectorAll('.product-suggestions').forEach(suggestions => {
        suggestions.style.display = 'none';
        suggestions.innerHTML = '';
    });
    activeProductRow = null;
    currentProductSuggestions = [];
    highlightedProductIndex = -1;
}

function selectProductSuggestion(row, product) {
    const previousProductId = row.querySelector('[data-product-id]').value;
    row.querySelector('[data-product-id]').value = product.id;
    row.querySelector('[data-product-search]').value = product.name;

    if (previousProductId && String(previousProductId) !== String(product.id)) {
        row.querySelector('[name$="[serial_numbers]"]').value = '';
    }

    const unitPrice = row.querySelector('.unit-price');
    if (unitPrice && (!unitPrice.value || Number(unitPrice.value) === 0)) {
        unitPrice.value = product.sale_price || '';
    }

    hideProductSuggestions();
    refreshSerialOptions(row);
    updateRowTotal(row);
    updateTotals();
}

function clearSelectedProduct(row) {
    row.querySelector('[data-product-id]').value = '';
    row.querySelector('[name$="[serial_numbers]"]').value = '';
    refreshSerialOptions(row);
}

function refreshHighlightedProduct() {
    if (!activeProductRow) return;

    activeProductRow.querySelectorAll('.product-suggestions .customer-suggestion').forEach((button, index) => {
        const active = index === highlightedProductIndex;
        button.classList.toggle('is-active', active);
        if (active) {
            button.scrollIntoView({ block: 'nearest' });
        }
    });
}

function renderProductSuggestions(row) {
    const input = row.querySelector('[data-product-search]');
    const suggestions = row.querySelector('.product-suggestions');
    const query = input.value.trim().toLowerCase();

    suggestions.innerHTML = '';
    activeProductRow = row;

    if (query.length < 1) {
        hideProductSuggestions();
        return;
    }

    currentProductSuggestions = products
        .filter(product => productSearchText(product).includes(query))
        .slice(0, 10);
    highlightedProductIndex = currentProductSuggestions.length > 0 ? 0 : -1;

    if (currentProductSuggestions.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'customer-suggestion';
        empty.innerHTML = '<strong>No inventory product found</strong><span>This line will be saved as text only.</span>';
        suggestions.appendChild(empty);
        suggestions.style.display = 'block';
        return;
    }

    currentProductSuggestions.forEach((product, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'customer-suggestion';
        button.innerHTML = `<strong>${escapeHtml(product.name)}</strong><span>${escapeHtml(productLabel(product))}</span>`;
        button.addEventListener('mouseenter', () => {
            highlightedProductIndex = index;
            refreshHighlightedProduct();
        });
        button.addEventListener('click', () => selectProductSuggestion(row, product));
        suggestions.appendChild(button);
    });

    suggestions.style.display = 'block';
    refreshHighlightedProduct();
}

let itemIndex = {{ count($invoiceItems) }};

document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <div>
            <label for="items_${itemIndex}_product_name">Product Name <span class="required-mark">*</span></label>
            <div class="product-picker">
                <input type="hidden" name="items[${itemIndex}][product_id]" data-product-id>
                <input id="items_${itemIndex}_product_name" type="text" name="items[${itemIndex}][product_name]" placeholder="Router, cable, setup fee" autocomplete="off" data-product-search required>
                <div class="customer-suggestions product-suggestions"></div>
            </div>
        </div>
        <div>
            <label for="items_${itemIndex}_quantity">Qty <span class="required-mark">*</span></label>
            <input id="items_${itemIndex}_quantity" type="number" name="items[${itemIndex}][quantity]" min="1" value="1" class="quantity" required>
        </div>
        <div>
            <label for="items_${itemIndex}_unit_price">Unit Price <span class="required-mark">*</span></label>
            <input id="items_${itemIndex}_unit_price" type="number" name="items[${itemIndex}][unit_price]" step="0.01" min="0" class="unit-price" required>
        </div>
        <div>
            <label for="items_${itemIndex}_total">Total</label>
            <input id="items_${itemIndex}_total" type="number" name="items[${itemIndex}][total]" step="0.01" min="0" class="total" readonly tabindex="-1">
        </div>
        <div>
            <button type="button" class="btn light remove-item" aria-label="Remove item">X</button>
        </div>
        <div class="item-serials">
            <label for="items_${itemIndex}_serial_numbers">Serial Numbers</label>
            <textarea id="items_${itemIndex}_serial_numbers" name="items[${itemIndex}][serial_numbers]" rows="2" placeholder="Select a serial-tracked product first"></textarea>
            <div class="serial-options"></div>
            <span class="field-note">Click available serials to add/remove them, or type one per line.</span>
        </div>
    `;
    container.appendChild(newRow);
    refreshSerialOptions(newRow);
    itemIndex++;
    refreshRemoveButtons();
    updateTotals();
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        e.target.closest('.item-row').remove();
        refreshRemoveButtons();
        updateTotals();
    }
});

document.addEventListener('input', function(e) {
    if (e.target.matches('[data-product-search]')) {
        const row = e.target.closest('.item-row');
        clearSelectedProduct(row);
        renderProductSuggestions(row);
        return;
    }

    if (e.target.matches('[name$="[serial_numbers]"]')) {
        const row = e.target.closest('.item-row');
        syncQuantityToSerials(row);
        refreshSerialOptions(row);
        return;
    }

    if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price') || ['discount', 'vat', 'discountType', 'vatType'].includes(e.target.id)) {
        const row = e.target.closest('.item-row');
        if (row) {
            updateRowTotal(row);
        }
        updateTotals();
    }
});

document.addEventListener('focusin', function(e) {
    if (e.target.matches('[data-product-search]') && e.target.value.trim()) {
        renderProductSuggestions(e.target.closest('.item-row'));
    }
});

document.addEventListener('keydown', function(e) {
    if (!e.target.matches('[data-product-search]') || !activeProductRow) return;

    const suggestionsVisible = activeProductRow.querySelector('.product-suggestions').style.display === 'block';
    if (!suggestionsVisible || currentProductSuggestions.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedProductIndex = (highlightedProductIndex + 1) % currentProductSuggestions.length;
        refreshHighlightedProduct();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedProductIndex = (highlightedProductIndex - 1 + currentProductSuggestions.length) % currentProductSuggestions.length;
        refreshHighlightedProduct();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        selectProductSuggestion(activeProductRow, currentProductSuggestions[highlightedProductIndex]);
    } else if (e.key === 'Escape') {
        hideProductSuggestions();
    }
});

document.addEventListener('change', function(e) {
    if (['discountType', 'vatType'].includes(e.target.id)) {
        updateTotals();
    }
});

function updateRowTotal(row) {
    const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
    const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
    const total = quantity * unitPrice;
    row.querySelector('.total').value = total.toFixed(2);
}

function updateTotals() {
    document.querySelectorAll('.item-row').forEach(updateRowTotal);

    const totals = document.querySelectorAll('.total');
    let subtotal = 0;
    totals.forEach(total => {
        subtotal += parseFloat(total.value) || 0;
    });

    const discountInput = parseFloat(document.getElementById('discount').value) || 0;
    const vatInput = parseFloat(document.getElementById('vat').value) || 0;
    const discountType = document.getElementById('discountType').value;
    const vatType = document.getElementById('vatType').value;
    const discount = discountType === 'percent' ? subtotal * discountInput / 100 : discountInput;
    const afterDiscount = Math.max(0, subtotal - discount);
    const vat = vatType === 'percent' ? afterDiscount * vatInput / 100 : vatInput;
    const grandTotal = Math.max(0, afterDiscount + vat);

    document.getElementById('itemCount').textContent = totals.length;
    document.getElementById('subtotalAmount').textContent = subtotal.toFixed(2);
    document.getElementById('discountAmount').textContent = discount.toFixed(2);
    document.getElementById('vatAmount').textContent = vat.toFixed(2);
    document.getElementById('discountLabel').textContent = discountType === 'percent' ? `${discountInput.toFixed(2)}% = BDT` : 'BDT';
    document.getElementById('vatLabel').textContent = vatType === 'percent' ? `${vatInput.toFixed(2)}% = BDT` : 'BDT';
    document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
}

function refreshRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
        const button = row.querySelector('.remove-item');
        button.style.display = rows.length === 1 ? 'none' : 'inline-flex';
    });
}

refreshRemoveButtons();
document.querySelectorAll('.item-row').forEach(refreshSerialOptions);
updateTotals();
</script>
@endsection
