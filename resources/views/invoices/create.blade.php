@extends('layouts.app')

@section('content')
@php
    $isEdit = isset($invoice);
    $invoiceItems = old('items', $isEdit
        ? $invoice->items->map(fn ($item) => [
            'product_name' => $item->product_name,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->toArray()
        : [
        [
            'product_name' => '',
            'quantity' => 1,
            'unit_price' => '',
        ],
    ]);
    $selectedCustomer = old('customer_id')
        ? $customers->firstWhere('id', (int) old('customer_id'))
        : ($isEdit ? $invoice->customer : null);
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
                            <p>Select who this invoice is for and define the billing period.</p>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="invoice-form-grid">
                            <div class="customer-picker">
                                <label for="customer_name">Customer Name <span class="required-mark">*</span></label>
                                <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id') }}">
                                <input id="customer_name" name="customer_name" value="{{ old('customer_name', $selectedCustomer?->name) }}" autocomplete="off" placeholder="Type customer name or mobile number" required>
                                <div id="customerSuggestions" class="customer-suggestions"></div>
                                <span class="field-note">Start typing a name or mobile number. Select an existing customer, or continue with a new customer.</span>
                            </div>

                            <div>
                                <label for="customer_phone">Mobile Number <span class="required-mark">*</span></label>
                                <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $selectedCustomer?->phone) }}" autocomplete="off" placeholder="Customer mobile number" required>
                                <span class="field-note">Existing customer suggestions also show mobile numbers.</span>
                            </div>

                            <div>
                                <label for="billing_month">Billing Month <span class="required-mark">*</span></label>
                                <input id="billing_month" type="month" name="billing_month" value="{{ old('billing_month', $isEdit ? $invoice->billing_month : now()->format('Y-m')) }}" required>
                            </div>

                            <div>
                                <label for="discount">Discount <span class="required-mark">*</span></label>
                                <input id="discount" type="number" name="discount" step="0.01" min="0" value="{{ old('discount', $isEdit ? $invoice->discount : '0.00') }}" required>
                                <span class="field-note">Discount is deducted from the item subtotal.</span>
                            </div>

                            <div>
                                <label for="vat">VAT <span class="required-mark">*</span></label>
                                <input id="vat" type="number" name="vat" step="0.01" min="0" value="{{ old('vat', $isEdit ? ($invoice->vat ?? '0.00') : '0.00') }}" required>
                                <span class="field-note">VAT is added after discount.</span>
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
                                        <input id="items_{{ $index }}_product_name" type="text" name="items[{{ $index }}][product_name]" value="{{ $item['product_name'] ?? '' }}" placeholder="Router, cable, setup fee" required>
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
                                        <input id="items_{{ $index }}_total" type="number" name="items[{{ $index }}][total]" step="0.01" min="0" class="total" readonly>
                                    </div>
                                    <div>
                                        <button type="button" class="btn light remove-item" @if (count($invoiceItems) === 1) style="display:none;" @endif aria-label="Remove item">X</button>
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
                    <strong>BDT <span id="discountAmount">0.00</span></strong>
                </div>
                <div class="summary-row">
                    <span>VAT</span>
                    <strong>BDT <span id="vatAmount">0.00</span></strong>
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
const customerSuggestions = document.getElementById('customerSuggestions');
let customerSearchTimer;

function clearSelectedCustomer() {
    customerIdInput.value = '';
}

function hideCustomerSuggestions() {
    customerSuggestions.style.display = 'none';
    customerSuggestions.innerHTML = '';
}

function renderCustomerSuggestions(customers) {
    customerSuggestions.innerHTML = '';

    if (customers.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'customer-suggestion';
        empty.innerHTML = '<strong>No existing customer found</strong><span>This will be added as a new customer when the invoice is saved.</span>';
        customerSuggestions.appendChild(empty);
        customerSuggestions.style.display = 'block';
        return;
    }

    customers.forEach(customer => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'customer-suggestion';
        button.innerHTML = `
            <strong>${customer.name}</strong>
            <span>${customer.phone || 'No mobile'} · ${customer.connection_id}</span>
        `;
        button.addEventListener('click', () => {
            customerIdInput.value = customer.id;
            customerNameInput.value = customer.name;
            customerPhoneInput.value = customer.phone || '';
            hideCustomerSuggestions();
        });
        customerSuggestions.appendChild(button);
    });

    customerSuggestions.style.display = 'block';
}

function searchCustomers() {
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
    searchCustomers();
});

customerPhoneInput.addEventListener('input', () => {
    clearSelectedCustomer();
    searchCustomers();
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.customer-picker') && e.target !== customerPhoneInput) {
        hideCustomerSuggestions();
    }
});

let itemIndex = {{ count($invoiceItems) }};

document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <div>
            <label for="items_${itemIndex}_product_name">Product Name <span class="required-mark">*</span></label>
            <input id="items_${itemIndex}_product_name" type="text" name="items[${itemIndex}][product_name]" placeholder="Router, cable, setup fee" required>
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
            <input id="items_${itemIndex}_total" type="number" name="items[${itemIndex}][total]" step="0.01" min="0" class="total" readonly>
        </div>
        <div>
            <button type="button" class="btn light remove-item" aria-label="Remove item">X</button>
        </div>
    `;
    container.appendChild(newRow);
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
    if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price') || e.target.id === 'discount' || e.target.id === 'vat') {
        const row = e.target.closest('.item-row');
        if (row) {
            updateRowTotal(row);
        }
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

    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const vat = parseFloat(document.getElementById('vat').value) || 0;
    const grandTotal = Math.max(0, subtotal - discount + vat);

    document.getElementById('itemCount').textContent = totals.length;
    document.getElementById('subtotalAmount').textContent = subtotal.toFixed(2);
    document.getElementById('discountAmount').textContent = discount.toFixed(2);
    document.getElementById('vatAmount').textContent = vat.toFixed(2);
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
updateTotals();
</script>
@endsection
