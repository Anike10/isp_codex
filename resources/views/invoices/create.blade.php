@extends('layouts.app')

@section('content')
@php
    $isQuotation = ($documentMode ?? 'invoice') === 'quotation';
    $document = $isQuotation ? ($quotation ?? null) : ($invoice ?? null);
    $isEdit = $document !== null;
    $selectedInvoiceType = old('invoice_type', $isEdit ? ($document->invoice_type ?? 'product') : request('type', 'product'));
    $discountType = old('discount_type', $isEdit ? ($document->discount_type ?? 'amount') : 'amount');
    $discountValue = old('discount', $isEdit ? ($document->discount_value ?? ($document->discount ?? '0.00')) : '0.00');
    $vatType = old('vat_type', $isEdit ? ($document->vat_type ?? 'amount') : 'amount');
    $vatValue = old('vat', $isEdit ? ($document->vat_value ?? ($document->vat ?? '0.00')) : '0.00');
    $invoiceDate = old('billing_month', $isEdit ? ($document->billing_month ?? now()->format('Y-m')) : now()->format('Y-m'));
    $quotationDate = old('quotation_date', $isEdit ? optional($document->quotation_date)?->format('Y-m-d') : now()->format('Y-m-d'));
    $validUntil = old('valid_until', $isEdit ? optional($document->valid_until)?->format('Y-m-d') : '');
    $dueDate = old('due_date', $isEdit ? optional($document->due_date)?->format('Y-m-d') : null);
    $defaultProductName = $selectedInvoiceType === 'service' ? 'Service charge' : '';
    $selectedCustomer = old('customer_id')
        ? $customers->firstWhere('id', (int) old('customer_id'))
        : ($isEdit ? $document->customer : null);
    $selectedCustomerId = old('customer_id', $selectedCustomer?->id);
    $selectedCustomerPhone = old('customer_phone', $selectedCustomer?->phone);
    $selectedCustomerName = old('customer_name', $selectedCustomer?->name);
    $invoiceItems = old('items', $isEdit
        ? $document->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'quantity' => (string) $item->quantity,
            'unit_price' => (string) $item->unit_price,
            'line_discount' => 0,
            'serial_numbers' => $item->serial_numbers ?? '',
            'serialless_quantity' => (string) ($item->serialless_quantity ?? ''),
            'track_serial_numbers' => $item->product?->track_serial_numbers ? '1' : '0',
        ])->toArray()
        : [[
            'product_id' => '',
            'product_name' => $defaultProductName,
            'quantity' => '1',
            'unit_price' => '',
            'line_discount' => '0',
            'serial_numbers' => '',
            'serialless_quantity' => '',
            'track_serial_numbers' => '1',
        ]]);

    $invoiceItems = collect($invoiceItems)->map(function (array $item): array {
        return [
            'product_id' => $item['product_id'] ?? '',
            'product_name' => $item['product_name'] ?? '',
            'quantity' => (string) ($item['quantity'] ?? 1),
            'unit_price' => (string) ($item['unit_price'] ?? ''),
            'line_discount' => (string) ($item['line_discount'] ?? 0),
            'serial_numbers' => app(\App\Support\SerialNumberParser::class)->formatCompact($item['serial_numbers'] ?? ''),
            'serialless_quantity' => (string) ($item['serialless_quantity'] ?? ''),
            'track_serial_numbers' => (string) ($item['track_serial_numbers'] ?? '0'),
        ];
    })->all();

    $documentLabel = $isQuotation ? 'Quotation' : 'Invoice';
    $pageTitle = $isEdit ? 'Edit '.$documentLabel : 'Create '.$documentLabel;
    $indexRoute = $isQuotation ? route('quotations.index') : route('invoices.index');

    $formAction = $isQuotation
        ? ($isEdit ? route('quotations.update', $document) : route('quotations.store'))
        : ($isEdit ? route('invoices.update', $document) : route('invoices.store'));
    $cancelRoute = $isEdit
        ? ($isQuotation ? route('quotations.show', $document) : route('invoices.show', $document))
        : $indexRoute;

    $initialSubtotal = (float) ($document?->subtotal ?? 0);
    $initialDiscountAmount = (float) ($document?->discount ?? 0);
    $initialVatAmount = (float) ($document?->vat ?? 0);
    $initialResellerCommission = (float) ($document?->reseller_commission_amount ?? 0);
    $initialResellerPercent = (float) ($document?->reseller_commission_percent ?? ($selectedCustomerSummary?->reseller_commission_percent ?? 0));
    $initialGrandTotal = (float) ($document?->total ?? 0);
    $runningDue = (float) ($selectedCustomerSummary?->running_due ?? 0);
    $advanceBalance = (float) ($selectedCustomerSummary?->account_balance ?? 0);
    $resellerPercent = (float) ($selectedCustomerSummary?->reseller_commission_percent ?? 0);
    $canFinalize = auth()->user()?->can('finalize_invoices');
    $warehouses = $warehouses ?? collect();
    $defaultWarehouseId = old('warehouse_id', $defaultWarehouseId ?? null);
@endphp

<style>
    .invoice-create-shell {
        display: grid;
        gap: 14px;
    }

    .toolbar-wrap {
        position: sticky;
        top: 8px;
        z-index: 30;
        display: grid;
        gap: 12px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #d9e2f1;
        border-radius: 14px;
        padding: 12px;
        backdrop-filter: blur(4px);
        box-shadow: 0 12px 30px rgba(16, 42, 67, 0.08);
    }

    .toolbar-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .toolbar-title h1 {
        margin: 0;
        font-size: 1.5rem;
    }

    .toolbar-title p {
        margin: 4px 0 0;
        color: #5f6b80;
    }

    .toolbar-compact {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .help-tip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 999px;
        background: #eef4ff;
        color: #3f4f96;
        font-size: 11px;
        margin-left: 6px;
        font-weight: 700;
        border: 1px solid #ccd9f5;
        cursor: help;
    }

    .compact-field {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .compact-field label {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 700;
        color: #22314a;
        font-size: 13px;
    }

    .compact-field .required {
        color: #b42318;
    }

    .compact-field input,
    .compact-field select {
        width: 100%;
        min-height: 39px;
        border: 1px solid #c7d4e6;
        border-radius: 10px;
        padding: 8px 10px;
        background: #fff;
    }

    .toolbar-search {
        position: relative;
    }

    .suggestion-list {
        display: none;
        position: absolute;
        z-index: 60;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        max-height: 240px;
        overflow: auto;
        background: #fff;
        border: 1px solid #ccd7e8;
        border-radius: 10px;
        box-shadow: 0 16px 30px rgba(11, 16, 34, 0.15);
    }

    .suggestion-list button {
        display: block;
        width: 100%;
        border: 0;
        border-bottom: 1px solid #edf1f6;
        background: #fff;
        text-align: left;
        padding: 10px;
        font: inherit;
        cursor: pointer;
        color: #1f2937;
    }

    .suggestion-list button:hover,
    .suggestion-list button.is-active {
        background: #f0f6ff;
    }

    .suggestion-list button strong {
        display: block;
    }

    .suggestion-list button span {
        display: block;
        color: #667085;
        font-size: 12px;
        margin-top: 2px;
    }

    .create-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 14px;
        align-items: start;
    }

    .section-card {
        background: #ffffff;
        border: 1px solid #d2dcef;
        border-radius: 14px;
        box-shadow: 0 10px 25px rgba(31, 48, 67, 0.06);
    }

    .section-head {
        border-bottom: 1px solid #e6edf5;
        padding: 13px 16px;
        background: linear-gradient(130deg, #f3f7fb, #f9fbff);
    }

    .section-head h2 {
        margin: 0;
        font-size: 1.1rem;
    }

    .section-head p {
        margin: 6px 0 0;
        color: #607089;
        font-size: 0.92rem;
    }

    .section-body {
        padding: 14px 16px;
    }

    .input-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .input-grid .field-full {
        grid-column: 1 / -1;
    }

    .field,
    .line-field {
        display: grid;
        gap: 6px;
    }

    .field label,
    .line-field label {
        font-size: 12px;
        font-weight: 700;
        color: #233252;
    }

    .line-field label {
        font-size: 11px;
    }

    .line-field small,
    .field small {
        color: #667085;
        font-size: 11px;
        margin-top: -2px;
    }

    .amount-pair {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 110px;
        gap: 8px;
        align-items: center;
    }

    .line-table-head {
        display: grid;
        grid-template-columns: minmax(220px, 2.2fr) minmax(150px, 1.2fr) 90px 110px 110px 100px 70px;
        gap: 10px;
        padding: 0 10px 8px;
        color: #667085;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        border-bottom: 1px dashed #d2dded;
    }

    .line-item-row {
        display: grid;
        grid-template-columns: minmax(220px, 2.2fr) minmax(150px, 1.2fr) 90px 110px 110px 100px 70px;
        gap: 10px;
        align-items: start;
        padding: 10px;
        border: 1px solid #e2e9f2;
        border-radius: 12px;
        background: #fff;
        margin-bottom: 10px;
    }

    .line-item-row:hover {
        border-color: #bccae0;
        box-shadow: 0 8px 20px rgba(24, 39, 63, 0.06);
    }

    .line-field input,
    .line-field textarea,
    .line-field select,
    .compact-field input,
    .compact-field select,
    .field input,
    .field textarea {
        width: 100%;
        border: 1px solid #cbd7e8;
        border-radius: 9px;
        padding: 8px 10px;
        min-height: 36px;
        font: inherit;
        background: #fff;
    }

    .line-field textarea {
        min-height: 72px;
        resize: vertical;
    }

    .line-field input[readonly],
    .line-field textarea[readonly] {
        background: #f8fafc;
        color: #334155;
    }

    .serial-option-area {
        display: grid;
        gap: 6px;
    }

    .serial-option-area label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: #2e3f57;
        font-weight: 600;
    }

    .line-item-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .line-item-actions .btn {
        min-height: 36px;
        width: 100%;
    }

    .line-actions {
        margin-top: 8px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        align-items: center;
        border-top: 1px dashed #d3deea;
        padding-top: 12px;
    }

    .line-actions .btn {
        min-height: 38px;
    }

    .summary {
        position: sticky;
        top: 20px;
        padding: 16px;
    }

    .summary-grid {
        display: grid;
        gap: 8px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        color: #4b5563;
        padding: 7px 0;
        border-bottom: 1px dashed #e3eaf4;
    }

    .summary-row strong {
        color: #111827;
        font-weight: 700;
    }

    .summary-net {
        margin-top: 8px;
        border: 1px solid #bde4ce;
        border-radius: 11px;
        background: linear-gradient(135deg, #eefaf1, #ecf7ff);
        padding: 12px;
        color: #0f513e;
        display: grid;
        gap: 3px;
    }

    .summary-net strong {
        color: #065f46;
        font-size: 1.45rem;
    }

    .summary-actions {
        margin-top: 14px;
        display: grid;
        gap: 8px;
    }

    .summary-actions .btn {
        min-height: 42px;
        width: 100%;
        justify-content: center;
        font-weight: 700;
    }

    .summary-note {
        margin: 10px 0 0;
        color: #667085;
        font-size: 12px;
    }

    .action-row {
        margin-top: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .action-row .btn {
        min-height: 40px;
    }

    details.doc-notes {
        margin-top: 12px;
    }

    details.doc-notes summary {
        cursor: pointer;
        color: #1e40af;
        font-weight: 700;
        list-style: none;
    }

    details.doc-notes summary::-webkit-details-marker {
        display: none;
    }

    @media (max-width: 1220px) {
        .create-layout {
            grid-template-columns: 1fr;
        }

        .summary {
            position: static;
        }
    }

    @media (max-width: 840px) {
        .toolbar-compact,
        .line-table-head,
        .line-item-row {
            grid-template-columns: 1fr;
        }

        .line-item-row {
            padding: 10px;
            gap: 8px;
        }

        .line-table-head {
            display: none;
        }

        .line-item-row .line-field {
            order: initial;
        }

        .line-item-actions {
            justify-content: flex-start;
        }

        .line-item-actions .btn {
            width: auto;
        }
    }
</style>

<div class="invoice-create-shell">
    <div class="toolbar-wrap">
        <div class="toolbar-head">
            <div class="toolbar-title">
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $isQuotation ? 'Prepare and send quotation fast with minimal clicks.' : 'Create or edit invoice records in a compact, transaction-focused layout.' }}</p>
            </div>
            <a class="btn btn-light" href="{{ $cancelRoute }}">Back</a>
        </div>
        <div class="toolbar-compact">
            <div class="compact-field">
                <label for="documentMode">Document Type <span class="help-tip" title="Change here to jump between Invoice / Quotation screens.">i</span></label>
                <select id="documentMode" {{ $isEdit ? 'disabled' : '' }}>
                    <option value="invoice" @selected(!$isQuotation)>Invoice</option>
                    <option value="quotation" @selected($isQuotation)>Quotation</option>
                </select>
            </div>
            <div class="compact-field">
                <label for="customer_name">Party / Customer <span class="required">*</span> <span class="help-tip" title="Search by name, phone, or connection ID">i</span></label>
                <div class="toolbar-search">
                <input id="customer_name" name="customer_name" form="invoiceCreateForm" placeholder="Type customer name / phone..." value="{{ $selectedCustomerName }}" autocomplete="off">
                    <div id="customerSuggestions" class="suggestion-list"></div>
                </div>
                <input type="hidden" id="customer_id" name="customer_id" form="invoiceCreateForm" value="{{ $selectedCustomerId }}">
            </div>
            <div class="compact-field">
                <label for="{{ $isQuotation ? 'quotation_date' : 'billing_month' }}">{{ $isQuotation ? 'Quotation Date' : 'Billing Month' }} <span class="required">*</span> <span class="help-tip" title="Use the period this document belongs to.">i</span></label>
                <input id="documentDate" type="{{ $isQuotation ? 'date' : 'month' }}" name="{{ $isQuotation ? 'quotation_date' : 'billing_month' }}" value="{{ $isQuotation ? $quotationDate : $invoiceDate }}" required form="invoiceCreateForm">
                @if ($isQuotation)
                    <input type="hidden" id="billing_month" name="billing_month" form="invoiceCreateForm" value="{{ $invoiceDate }}">
                @endif
            </div>
            <div class="compact-field">
                <label for="warehouse_id">Warehouse <span class="help-tip" title="Select default for quick stock lookup while typing products.">i</span></label>
                <select id="warehouse_id" name="warehouse_id" form="invoiceCreateForm">
                    @forelse ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $defaultWarehouseId) === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                    @empty
                        <option value="">Default Warehouse</option>
                    @endforelse
                </select>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="margin: 0">
            <ul style="margin: 0; padding-left: 16px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ $formAction }}" id="invoiceCreateForm" autocomplete="off">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" id="initialSubtotal" value="{{ number_format($initialSubtotal, 2, '.', '') }}">
        <input type="hidden" id="initialDiscount" value="{{ number_format($initialDiscountAmount, 2, '.', '') }}">
        <input type="hidden" id="initialVat" value="{{ number_format($initialVatAmount, 2, '.', '') }}">
        <input type="hidden" id="initialResellerCommission" value="{{ number_format($initialResellerCommission, 2, '.', '') }}">
        <input type="hidden" id="initialResellerPercent" value="{{ number_format($initialResellerPercent, 2, '.', '') }}">

        <div class="create-layout">
            <div class="left-column">
                <section class="section-card">
                    <div class="section-head">
                        <h2>Customer & Document Details</h2>
                        <p>Keep required data compact. Optional notes are in one fold.</p>
                    </div>
                    <div class="section-body">
                        <div class="input-grid">
                            <div class="field">
                                <label for="customer_phone">Mobile Number <span class="required">*</span></label>
                                <input id="customer_phone" name="customer_phone" value="{{ $selectedCustomerPhone }}" placeholder="01xxxxxxxxx" required>
                            </div>
                            <div class="field">
                                <label for="invoice_type">Document Type Name <span class="required">*</span></label>
                                <select id="invoice_type" name="invoice_type">
                                    <option value="product" @selected($selectedInvoiceType === 'product')>Product / One-time</option>
                                    <option value="service" @selected($selectedInvoiceType === 'service')>Service Charge</option>
                                </select>
                            </div>
                            @if ($isQuotation)
                                <div class="field">
                                    <label for="valid_until">Valid Until</label>
                                    <input type="date" id="valid_until" name="valid_until" value="{{ $validUntil }}">
                                </div>
                                <div class="field">
                                    <label for="invoice_type_note">Type Hint</label>
                                    <input id="invoice_type_note" value="Quotation is non-accounting draft." readonly>
                                </div>
                            @else
                                <div class="field">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" id="due_date" name="due_date" value="{{ $dueDate }}">
                                </div>
                                <div class="field">
                                    <label for="due_date">Reference</label>
                                    <input id="reference_code" name="reference_code" placeholder="Optional reference">
                                </div>
                            @endif
                        </div>

                        <div class="input-grid" style="margin-top: 12px;">
                            <div class="field">
                                <label for="discount">Discount <span class="help-tip" title="Use fixed BDT value or percentage.">i</span></label>
                                <div class="amount-pair">
                                    <input id="discount" type="number" name="discount" step="0.01" min="0" value="{{ $discountValue }}">
                                    <select id="discountType" name="discount_type">
                                        <option value="amount" @selected($discountType === 'amount')>BDT</option>
                                        <option value="percent" @selected($discountType === 'percent')>%</option>
                                    </select>
                                </div>
                            </div>
                            <div class="field">
                                <label for="vat">VAT <span class="help-tip" title="Use VAT after discount.">i</span></label>
                                <div class="amount-pair">
                                    <input id="vat" type="number" name="vat" step="0.01" min="0" value="{{ $vatValue }}">
                                    <select id="vatType" name="vat_type">
                                        <option value="amount" @selected($vatType === 'amount')>BDT</option>
                                        <option value="percent" @selected($vatType === 'percent')>%</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <details class="doc-notes">
                            <summary>Notes & Payment Text (Optional)</summary>
                            <div class="input-grid" style="margin-top: 10px;">
                                <div class="field field-full">
                                    <label for="payment_note">Payment Note</label>
                                    <textarea id="payment_note" name="payment_note" rows="3">{{ old('payment_note', $isEdit ? ($document->payment_note ?? $defaultPaymentNote) : $defaultPaymentNote) }}</textarea>
                                </div>
                                <div class="field">
                                    <label for="public_note">Public Note</label>
                                    <textarea id="public_note" name="public_note" rows="3">{{ old('public_note', $isEdit ? $document->public_note : '') }}</textarea>
                                </div>
                                <div class="field">
                                    <label for="private_note">Private Note</label>
                                    <textarea id="private_note" name="private_note" rows="3">{{ old('private_note', $isEdit ? $document->private_note : '') }}</textarea>
                                </div>
                                <div class="field">
                                    <label class="checkbox-line" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700;">
                                        <input type="checkbox" id="show_public_note" name="show_public_note" value="1" @checked((bool) old('show_public_note', $isEdit ? (bool) ($document->show_public_note ?? false) : false))>
                                        <span>Show Public Note on Print</span>
                                    </label>
                                </div>
                            </div>
                        </details>
                    </div>
                </section>

                <section class="section-card" style="margin-top: 14px;">
                    <div class="section-head">
                        <h2>Line Items</h2>
                        <p>Add products/services and keep row values focused for fast data entry.</p>
                    </div>
                    <div class="section-body">
                        <div class="line-table-head">
                            <div>Product</div>
                            <div>Serial</div>
                            <div>Qty</div>
                            <div>Rate</div>
                            <div>Line Disc.</div>
                            <div>Total</div>
                            <div>Action</div>
                        </div>
                        <div id="lineItemsContainer"></div>
                        <div class="line-actions">
                            <button type="button" id="addLineItemBtn" class="btn btn-success">+ Add Line Item</button>
                            <span class="summary-note">Tip: Use Enter inside product field for quick selection.</span>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="section-card summary">
                <div class="section-head">
                    <h2>Billing Summary</h2>
                    <p>Live preview updates while typing.</p>
                </div>
                <div class="section-body">
                    <div class="summary-grid">
                        <div class="summary-row">
                            <span>Running Due</span>
                            <strong id="summaryRunningDue">{{ number_format($runningDue, 2, '.', ',') }}</strong>
                        </div>
                        <div class="summary-row">
                            <span>Advance Balance</span>
                            <strong id="summaryAdvanceBalance">{{ number_format($advanceBalance, 2, '.', ',') }}</strong>
                        </div>
                        <div class="summary-row">
                            <span>Net Balance</span>
                            <strong id="summaryNetBalance">{{ number_format(max(0, $runningDue - $advanceBalance), 2, '.', ',') }}</strong>
                        </div>
                    </div>

                    <div class="summary-grid" style="margin-top: 12px;">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <strong id="summarySubtotal">0.00</strong>
                        </div>
                        <div class="summary-row">
                            <span>Discount</span>
                            <strong id="summaryDiscount">0.00</strong>
                        </div>
                        <div class="summary-row">
                            <span>VAT</span>
                            <strong id="summaryVat">0.00</strong>
                        </div>
                        <div class="summary-row">
                            <span>Reseller Commission</span>
                            <strong id="summaryReseller">0.00</strong>
                        </div>
                        <div class="summary-row">
                            <span>Total Amount</span>
                            <strong id="summaryTotalAmount">0.00</strong>
                        </div>
                    </div>
                    <div class="summary-net">
                        <span>Net Due</span>
                        <strong id="summaryNetDue">0.00</strong>
                    </div>

                    <div class="summary-actions">
                        <button type="submit" class="btn btn-outline-secondary" name="document_action" value="draft">{{ $isEdit ? 'Update Draft' : 'Save Draft' }}</button>
                        @if ($isQuotation)
                            <button type="submit" class="btn btn-success" name="document_action" value="finalize">Save Quotation</button>
                        @else
                            <button type="submit" class="btn btn-success" name="document_action" value="finalize" id="finalizeButton">Save / Finalize {{ $documentLabel }}</button>
                        @endif
                    </div>
                    <p class="summary-note">
                        @if (! $isQuotation)
                            Draft save stores as editable. Finalize requires permission.
                        @else
                            Quotation save does not touch accounting balances.
                        @endif
                    </p>
                </div>
            </aside>
        </div>
    </form>
</div>

<template id="line-item-template">
    <div class="line-item-row">
        <div class="line-field">
            <label>Product <span class="required">*</span></label>
            <input type="hidden" class="line-product-id">
            <div class="toolbar-search">
                <input type="text" class="line-product-search" placeholder="Router, Cable, Service..." autocomplete="off">
                <div class="line-suggestions suggestion-list"></div>
            </div>
            <small>Type to search</small>
        </div>
        <div class="line-field">
            <label>Serial Numbers</label>
                <div class="serial-option-area">
                    <textarea class="line-serials" rows="1" placeholder="SN1, SN2..."></textarea>
                    <label>
                        <input type="checkbox" class="line-track-toggle" checked> Track serial / stock adjustment
                    </label>
                    <input type="hidden" class="line-track-hidden">
                    <input type="number" class="line-serialless" min="0" placeholder="Serial-less qty">
                    <small class="stock-count">Serial-less stock: <span class="line-serialless-stock">0</span></small>
                </div>
            </div>
        <div class="line-field">
            <label>Qty</label>
            <input type="number" class="line-qty" min="1" value="1">
        </div>
        <div class="line-field">
            <label>Unit Price</label>
            <input type="number" class="line-unit-price" step="0.01" min="0">
        </div>
        <div class="line-field">
            <label>Line Discount</label>
            <input type="number" class="line-line-discount" step="0.01" min="0" value="0">
        </div>
        <div class="line-field">
            <label>Total</label>
            <input type="number" class="line-total" readonly>
        </div>
        <div class="line-item-actions">
            <button type="button" class="btn btn-light remove-line-item">Remove</button>
        </div>
    </div>
</template>

<input type="hidden" id="initialCustomerResellerPercent" value="{{ number_format($resellerPercent, 2, '.', '') }}">
<script>
(() => {
    const products = @json($productSuggestionData, JSON_UNESCAPED_UNICODE);
    const initialItems = @json($invoiceItems, JSON_UNESCAPED_UNICODE);
    const isQuotation = @json($isQuotation);
    const routeMap = {
        invoice: '{{ route('invoices.create') }}',
        quotation: '{{ route('quotations.create') }}',
    };

    const summaryState = {
        runningDue: {{ json_encode((float) $runningDue) }},
        advanceBalance: {{ json_encode((float) $advanceBalance) }},
        resellerPercent: {{ json_encode((float) ($initialResellerPercent > 0 ? $initialResellerPercent : $resellerPercent)) }},
    };
    const canFinalize = {{ json_encode($canFinalize) }};
    const isEdit = @json($isEdit);

    const documentMode = document.getElementById('documentMode');
    const customerNameInput = document.getElementById('customer_name');
    const customerPhoneInput = document.getElementById('customer_phone');
    const customerIdInput = document.getElementById('customer_id');
    const customerSuggestions = document.getElementById('customerSuggestions');
    const docDate = document.getElementById('documentDate');
    const warehouseSelect = document.getElementById('warehouse_id');
    const container = document.getElementById('lineItemsContainer');
    const lineTemplate = document.getElementById('line-item-template');
    const addLineItemButton = document.getElementById('addLineItemBtn');
    const form = document.getElementById('invoiceCreateForm');
    const discountInput = document.getElementById('discount');
    const discountType = document.getElementById('discountType');
    const vatInput = document.getElementById('vat');
    const vatType = document.getElementById('vatType');
    const summaryRows = {
        subtotal: document.getElementById('summarySubtotal'),
        discount: document.getElementById('summaryDiscount'),
        vat: document.getElementById('summaryVat'),
        reseller: document.getElementById('summaryReseller'),
        total: document.getElementById('summaryTotalAmount'),
        netDue: document.getElementById('summaryNetDue'),
        runningDue: document.getElementById('summaryRunningDue'),
        advance: document.getElementById('summaryAdvanceBalance'),
        netBalance: document.getElementById('summaryNetBalance'),
    };

    let activeProductRow = null;
    let currentProductMatches = [];
    let activeProductIndex = -1;
    let activeCustomerRow = -1;
    let customerSuggestionsData = [];
    let customerTimer = null;

    function toNumber(value) {
        const num = parseFloat(value);
        return Number.isFinite(num) ? num : 0;
    }

    function money(value) {
        return toNumber(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function productSearchText(product) {
        return `${product.name || ''} ${product.sku || ''} ${product.barcode || ''} ${product.brand || ''}`.toLowerCase();
    }

    function lineTotals() {
        return Array.from(container.querySelectorAll('.line-item-row'));
    }

    function syncTrackFields(row) {
        const toggle = row.querySelector('.line-track-toggle');
        const serialNumbers = row.querySelector('.line-serials');
        const serialLess = row.querySelector('.line-serialless');
        const hidden = row.querySelector('.line-track-hidden');
        const trackSelected = toggle.checked;

        serialNumbers.disabled = !trackSelected;
        serialLess.disabled = !trackSelected;
        hidden.value = trackSelected ? '1' : '0';
    }

    function applyProductToRow(row, product) {
        row.querySelector('.line-product-id').value = product.id || '';
        const rowProduct = row.querySelector('.line-product-search');
        rowProduct.value = product.name || '';
        const unitInput = row.querySelector('.line-unit-price');
        if (!unitInput.value || Number(unitInput.value) === 0) {
            unitInput.value = product.sale_price || '';
        }

        const trackToggle = row.querySelector('.line-track-toggle');
        const stockSpan = row.querySelector('.line-serialless-stock');
        const serialLess = row.querySelector('.line-serialless');
        const serialNumbers = row.querySelector('.line-serials');

        if (product.track_serials) {
            trackToggle.checked = true;
            serialNumbers.value = serialNumbers.value || '';
            stockSpan.textContent = String(product.serialless_stock ?? 0);
        } else {
            trackToggle.checked = false;
            serialNumbers.value = '';
            serialLess.value = '';
            stockSpan.textContent = '0';
        }

        syncTrackFields(row);
        syncLineTotal(row);
        syncRowNames();
        updateSummary();
    }

    function hideProductSuggestions(row) {
        if (!row) {
            return;
        }
        const box = row.querySelector('.line-suggestions');
        box.style.display = 'none';
        box.innerHTML = '';
        if (activeProductRow === row) {
            activeProductRow = null;
        }
        currentProductMatches = [];
        activeProductIndex = -1;
    }

    function renderProductSuggestions(row) {
        const query = row.querySelector('.line-product-search').value.trim().toLowerCase();
        const suggestionPanel = row.querySelector('.line-suggestions');

        if (!query) {
            hideProductSuggestions(row);
            return;
        }

        suggestionPanel.innerHTML = '';
        currentProductMatches = products
            .filter((product) => productSearchText(product).includes(query))
            .slice(0, 8);
        activeProductIndex = currentProductMatches.length > 0 ? 0 : -1;

        if (currentProductMatches.length === 0) {
            const empty = document.createElement('div');
            empty.style.padding = '10px';
            empty.style.color = '#667085';
            empty.textContent = 'No product found.';
            suggestionPanel.appendChild(empty);
            suggestionPanel.style.display = 'block';
            return;
        }

        currentProductMatches.forEach((product, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.innerHTML = `<strong>${escapeHtml(product.name)}</strong><span>${escapeHtml(product.sku || 'SKU N/A')} • BDT ${money(product.sale_price || 0)}</span>`;
            button.className = index === 0 ? 'is-active' : '';
            button.addEventListener('click', () => {
                applyProductToRow(row, product);
                hideProductSuggestions(row);
                row.querySelector('.line-qty').focus();
            });
            button.addEventListener('mouseenter', () => {
                activeProductIndex = index;
                refreshSuggestionHighlight(suggestionPanel, row);
            });
            suggestionPanel.appendChild(button);
        });

        suggestionPanel.style.display = 'block';
        refreshSuggestionHighlight(suggestionPanel, row);
    }

    function refreshSuggestionHighlight(panel, row) {
        panel.querySelectorAll('button').forEach((button, index) => {
            button.classList.toggle('is-active', index === activeProductIndex);
            if (index === activeProductIndex) {
                button.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function syncLineTotal(row) {
        const qty = toNumber(row.querySelector('.line-qty').value);
        const rate = toNumber(row.querySelector('.line-unit-price').value);
        const lineDiscount = toNumber(row.querySelector('.line-line-discount').value);
        const total = Math.max(0, qty * rate - lineDiscount);
        row.querySelector('.line-total').value = total.toFixed(2);
    }

    function syncRowNames() {
        const rows = lineTotals();
        rows.forEach((row, index) => {
            row.querySelector('.line-product-id').name = `items[${index}][product_id]`;
            row.querySelector('.line-product-search').name = `items[${index}][product_name]`;
            row.querySelector('.line-qty').name = `items[${index}][quantity]`;
            row.querySelector('.line-unit-price').name = `items[${index}][unit_price]`;
            row.querySelector('.line-line-discount').name = `items[${index}][line_discount]`;
            row.querySelector('.line-total').name = `items[${index}][total]`;
            row.querySelector('.line-serials').name = `items[${index}][serial_numbers]`;
            row.querySelector('.line-serialless').name = `items[${index}][serialless_quantity]`;
            row.querySelector('.line-track-hidden').name = `items[${index}][track_serial_numbers]`;
        });
    }

    function updateSummary() {
        const rows = lineTotals();
        let subtotal = 0;

        rows.forEach((row) => {
            syncLineTotal(row);
            subtotal += toNumber(row.querySelector('.line-total').value);
        });

        const docDiscount = discountType.value === 'percent'
            ? subtotal * toNumber(discountInput.value) / 100
            : toNumber(discountInput.value);
        const afterDiscount = Math.max(0, subtotal - docDiscount);
        const vat = vatType.value === 'percent'
            ? afterDiscount * toNumber(vatInput.value) / 100
            : toNumber(vatInput.value);
        const total = Math.max(0, afterDiscount + vat);
        const resellerRate = isQuotation ? 0 : summaryState.resellerPercent;
        const resellerAmount = total * (resellerRate / 100);
        const netDue = Math.max(0, total - resellerAmount);

        summaryRows.subtotal.textContent = money(subtotal);
        summaryRows.discount.textContent = money(docDiscount);
        summaryRows.vat.textContent = money(vat);
        summaryRows.reseller.textContent = money(resellerAmount);
        summaryRows.total.textContent = money(total);
        summaryRows.netDue.textContent = money(netDue);
        summaryRows.runningDue.textContent = money(summaryState.runningDue);
        summaryRows.advance.textContent = money(summaryState.advanceBalance);
        summaryRows.netBalance.textContent = money(Math.max(0, summaryState.runningDue - summaryState.advanceBalance));

        if (!isEdit) {
            const suggestedDue = netDue;
            summaryRows.netDue.textContent = money(suggestedDue);
        }
    }

    function updateCustomerSummary(payload = null) {
        if (!payload) {
            payload = {
                running_due: summaryState.runningDue,
                account_balance: summaryState.advanceBalance,
                reseller_commission_percent: summaryState.resellerPercent,
            };
        }

        summaryState.runningDue = toNumber(payload.running_due || payload.runningDue);
        summaryState.advanceBalance = toNumber(payload.account_balance || payload.advance_balance || payload.advanceBalance);
        summaryState.resellerPercent = toNumber(payload.reseller_commission_percent || 0);
        updateSummary();
    }

    function appendLineRow(item = {}) {
        const node = lineTemplate.content.firstElementChild.cloneNode(true);
        node.querySelector('.line-product-id').value = item.product_id ?? '';
        node.querySelector('.line-product-search').value = item.product_name ?? '';
        node.querySelector('.line-qty').value = item.quantity ?? 1;
        node.querySelector('.line-unit-price').value = item.unit_price ?? '';
        node.querySelector('.line-line-discount').value = item.line_discount ?? 0;
        node.querySelector('.line-serials').value = item.serial_numbers ?? '';
        node.querySelector('.line-serialless').value = item.serialless_quantity ?? '';
        node.querySelector('.line-track-toggle').checked = String(item.track_serial_numbers ?? '0') === '1';
        node.querySelector('.line-track-hidden').value = node.querySelector('.line-track-toggle').checked ? '1' : '0';
        node.querySelector('.line-serialless-stock').textContent = '0';
        syncTrackFields(node);

        const product = products.find((productItem) => String(productItem.id) === String(item.product_id));
        if (product && String(product.track_serials) === '1') {
            node.querySelector('.line-serialless-stock').textContent = String(product.serialless_stock ?? 0);
        }

        container.appendChild(node);
        syncTrackFields(node);
        syncLineTotal(node);
        syncLineRows();
    }

    function syncLineRows() {
        syncRowNames();
        const rows = lineTotals();
        if (rows.length === 0) {
            appendLineRow();
            return;
        }

        if (rows.length === 1) {
            rows[0].querySelector('.remove-line-item').style.visibility = 'hidden';
        } else {
            rows.forEach((row) => {
                row.querySelector('.remove-line-item').style.visibility = 'visible';
            });
        }

        updateSummary();
    }

    function addLineRow() {
        appendLineRow({
            product_id: '',
            product_name: '',
            quantity: 1,
            unit_price: '',
            line_discount: 0,
            serial_numbers: '',
            serialless_quantity: '',
            track_serial_numbers: '0',
        });
    }

    function renderCustomerSuggestions(matches) {
        customerSuggestions.innerHTML = '';
        customerSuggestionsData = matches;
        activeCustomerRow = matches.length > 0 ? 0 : -1;

        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.style.padding = '10px';
            empty.style.color = '#667085';
            empty.textContent = 'No customer found.';
            customerSuggestions.appendChild(empty);
            customerSuggestions.style.display = 'block';
            return;
        }

        matches.forEach((customer, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.innerHTML = `<strong>${escapeHtml(customer.name || '')}</strong><span>${escapeHtml(customer.phone || '')} - Due ${money(customer.running_due || 0)}</span>`;
            button.className = index === 0 ? 'is-active' : '';
            button.addEventListener('click', () => {
                applyCustomerSelection(customer);
            });
            button.addEventListener('mouseenter', () => {
                activeCustomerRow = index;
                refreshCustomerHighlight();
            });
            customerSuggestions.appendChild(button);
        });

        customerSuggestions.style.display = 'block';
        refreshCustomerHighlight();
    }

    function refreshCustomerHighlight() {
        customerSuggestions.querySelectorAll('button').forEach((button, index) => {
            button.classList.toggle('is-active', index === activeCustomerRow);
            if (index === activeCustomerRow) {
                button.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    function applyCustomerSelection(customer) {
        customerSuggestions.style.display = 'none';
        customerSuggestions.innerHTML = '';
        customerNameInput.value = customer.name || '';
        customerPhoneInput.value = customer.phone || '';
        customerIdInput.value = customer.id;
        updateCustomerSummary(customer);
    }

    function loadCustomers(query) {
        if (!query || query.length < 2) {
            customerSuggestions.style.display = 'none';
            customerSuggestions.innerHTML = '';
            return;
        }

        fetch(`{{ route('invoice-customers.search') }}?q=${encodeURIComponent(query)}`, {
            headers: {
                Accept: 'application/json',
            },
        }).then((response) => response.json())
            .then((rows) => {
                renderCustomerSuggestions(rows);
            })
            .catch(() => {
                customerSuggestions.innerHTML = '';
                customerSuggestions.style.display = 'none';
            });
    }

    documentMode.addEventListener('change', () => {
        if (isEdit) {
            return;
        }
        const target = documentMode.value === 'quotation'
            ? routeMap.quotation
            : routeMap.invoice;
        window.location.href = target;
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('.toolbar-search')) {
            customerSuggestions.style.display = 'none';
            hideProductSuggestions(activeProductRow || null);
        }
    });

    customerNameInput.addEventListener('input', () => {
        customerIdInput.value = '';
        window.clearTimeout(customerTimer);
        const query = customerNameInput.value.trim();
        customerTimer = window.setTimeout(() => {
            loadCustomers(query);
        }, 220);
    });

    customerNameInput.addEventListener('keydown', function(event) {
        if (!customerSuggestions.style.display || customerSuggestions.style.display === 'none' || customerSuggestionsData.length === 0) {
            return;
        }
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeCustomerRow = (activeCustomerRow + 1) % customerSuggestionsData.length;
            refreshCustomerHighlight();
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeCustomerRow = (activeCustomerRow - 1 + customerSuggestionsData.length) % customerSuggestionsData.length;
            refreshCustomerHighlight();
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            if (activeCustomerRow >= 0) {
                applyCustomerSelection(customerSuggestionsData[activeCustomerRow]);
            }
        }
        if (event.key === 'Escape') {
            customerSuggestions.style.display = 'none';
        }
    });

    addLineItemButton.addEventListener('click', addLineRow);

    container.addEventListener('input', function(event) {
        const row = event.target.closest('.line-item-row');
        if (!row) {
            return;
        }

        if (event.target.classList.contains('line-product-search')) {
            activeProductRow = row;
            renderProductSuggestions(row);
        }

        if (event.target.classList.contains('line-track-toggle')) {
            syncTrackFields(row);
        }

        if (event.target.classList.contains('line-qty')
            || event.target.classList.contains('line-unit-price')
            || event.target.classList.contains('line-line-discount')) {
            syncLineTotal(row);
            updateSummary();
        }
    });

    container.addEventListener('focusin', function(event) {
        const row = event.target.closest('.line-item-row');
        if (!row || !event.target.classList.contains('line-product-search')) {
            return;
        }
        activeProductRow = row;
        renderProductSuggestions(row);
    });

    container.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-line-item')) {
            event.preventDefault();
            const row = event.target.closest('.line-item-row');
            if (!row) {
                return;
            }
            row.remove();
            syncLineRows();
            return;
        }

        if (event.target.closest('.line-suggestions') && !event.target.classList.contains('line-suggestions')) {
            return;
        }
    });

    container.addEventListener('keydown', function(event) {
        const row = event.target.closest('.line-item-row');
        if (!row || !event.target.classList.contains('line-product-search')) {
            return;
        }

        if (!activeProductRow || activeProductRow !== row) {
            activeProductRow = row;
        }

        const suggestions = row.querySelector('.line-suggestions');
        const suggestionsVisible = suggestions.style.display === 'block';
        if (!suggestionsVisible) {
            return;
        }

        const total = Math.max(0, suggestions.querySelectorAll('button').length);
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeProductIndex = (activeProductIndex + 1) % total;
            refreshSuggestionHighlight(suggestions, row);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeProductIndex = (activeProductIndex - 1 + total) % total;
            refreshSuggestionHighlight(suggestions, row);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const product = currentProductMatches[activeProductIndex];
            if (product) {
                applyProductToRow(row, product);
                hideProductSuggestions(row);
            }
        } else if (event.key === 'Escape') {
            hideProductSuggestions(row);
        }
    });

    if (discountInput && discountType) {
        discountInput.addEventListener('input', updateSummary);
        discountType.addEventListener('change', updateSummary);
    }
    if (vatInput && vatType) {
        vatInput.addEventListener('input', updateSummary);
        vatType.addEventListener('change', updateSummary);
    }

    form.addEventListener('submit', function() {
        const rows = lineTotals();
        if (rows.length === 0) {
            addLineRow();
        }

        syncRowNames();
        syncLineRows();
    });

    initialItems.forEach((item) => {
        appendLineRow(item);
    });

    if (container.children.length === 0) {
        addLineRow();
    }

    syncRowNames();
    updateCustomerSummary({
        running_due: summaryState.runningDue,
        account_balance: summaryState.advanceBalance,
        reseller_commission_percent: summaryState.resellerPercent,
    });
    updateSummary();
    const billingMonthField = document.getElementById('billing_month');
    const documentDateField = document.getElementById('documentDate');
    const syncBillingMonth = () => {
        if (!billingMonthField || !documentDateField) {
            return;
        }
        if (!documentDateField.value) {
            billingMonthField.value = '';
            return;
        }
        billingMonthField.value = documentDateField.value.substring(0, 7);
    };
    syncBillingMonth();
    documentDateField.addEventListener('change', syncBillingMonth);
    if (!canFinalize) {
        const finalizeBtn = document.getElementById('finalizeButton');
        if (finalizeBtn) {
            finalizeBtn.style.display = 'none';
        }
    }
})();
</script>
@endsection
