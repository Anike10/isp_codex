@extends('layouts.app')

@section('content')
@php
    $canManageInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canFinalizeInvoices = auth()->user()?->hasPermission('finalize_invoices');
    $canRecordPayments = auth()->user()?->hasPermission('manage_payments');
    $canBulkSelectInvoices = $canFinalizeInvoices || $canRecordPayments;
    $accountsByMethod = $paymentAccounts
        ->groupBy('payment_method')
        ->map(fn ($accounts) => $accounts->map(fn ($account) => [
            'id' => $account->id,
            'label' => $account->account_name.' - '.$account->account_number,
        ])->values())
        ->toArray();
@endphp

<style>
    .invoice-stats {
        margin-bottom: 16px;
    }

    .invoice-stat {
        display: grid;
        gap: 6px;
        min-height: 112px;
    }

    .invoice-stat span {
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .invoice-stat strong {
        font-size: 26px;
        line-height: 1.1;
    }

    .invoice-stat small {
        color: var(--muted);
        line-height: 1.35;
    }

    .invoice-generate-note {
        margin-top: 8px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.35;
    }

    .invoice-row-actions {
        min-width: 260px;
    }

    .invoice-row-actions .actions {
        justify-content: flex-end;
    }

    .invoice-row-actions .btn {
        min-height: 34px;
        padding: 8px 10px;
        font-size: 13px;
    }

    .invoice-table {
        overflow: visible;
    }

    .filter-check {
        display: inline-flex;
        gap: 8px;
        align-items: center;
        font-weight: 700;
        color: #475467;
    }

    .filter-check input {
        width: 16px;
        height: 16px;
        margin: 0;
    }

    .invoice-index-page .topbar { align-items:stretch; background:linear-gradient(135deg,#14213d,#193b5f 58%,#176b78); border:0; border-radius:18px; color:#fff; gap:22px; padding:24px; box-shadow:0 16px 34px rgba(20,33,61,.18); }
    .invoice-index-page .topbar .muted, .invoice-index-page .invoice-generate-note { color:rgba(255,255,255,.78); }
    .invoice-index-page .topbar h1 { color:#fff; margin-bottom:6px; }
    .invoice-quick-actions { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:8px; }
    .invoice-generate-form { display:flex; align-items:center; gap:8px; padding:8px; border-radius:12px; background:rgba(255,255,255,.12); }
    .invoice-generate-form input { width:150px; }
    .invoice-bulk-actions { display:grid; grid-template-columns:minmax(230px,.72fr) minmax(0,1.28fr); align-items:start; gap:16px; margin-bottom:16px; padding:18px; border:1px solid #d8e2ef; border-radius:16px; background:linear-gradient(135deg,#f8fbff,#eef7f6); box-shadow:0 10px 24px rgba(16,48,72,.08); }
    .invoice-bulk-heading { padding:10px 4px; }
    .invoice-bulk-heading strong { display:block; font-size:18px; color:#14213d; margin-bottom:6px; }
    .invoice-selection-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }

    .invoice-bulk-payment {
        grid-column: 1 / -1;
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-top: 0;
        padding: 14px;
        border: 1px solid #d8e2ef;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 6px 16px rgba(16,48,72,.06);
    }

    .invoice-bulk-payment .full {
        grid-column: 1 / -1;
    }
    .invoice-bulk-payment > .full:not(.actions) {
        grid-column: span 2;
        order: 5;
    }
    .invoice-bulk-payment > .full.actions {
        grid-column: span 1;
        order: 6;
        align-self: end;
    }
    .invoice-bulk-payment > div:nth-child(1) { order: 2; }
    .invoice-bulk-payment > div:nth-child(2) { order: 3; }
    .invoice-bulk-payment > div:nth-child(3) { order: 4; }
    .invoice-bulk-payment > div:nth-child(4) { order: 1; }

    .bulk-payment-status {
        align-self: end;
        grid-column: span 1;
        order: 6;
        padding: 11px 12px;
        border-radius: 10px;
        background: #f3f7fb;
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }

    .invoice-select-cell {
        width: 42px;
        text-align: center;
    }

    .invoice-select-cell input {
        width: 18px;
        height: 18px;
        margin: 0;
    }

    @media (max-width: 780px) {
        .invoice-index-page .topbar { padding:18px; }
        .invoice-quick-actions, .invoice-generate-form { justify-content:flex-start; }
        .invoice-generate-form { flex-wrap:wrap; }
        .invoice-bulk-actions { grid-template-columns:1fr; }
        .invoice-selection-actions { justify-content:flex-start; }
        .invoice-row-actions {
            min-width: 210px;
        }

        .invoice-bulk-actions {
            align-items: stretch;
        }

        .invoice-bulk-payment {
            grid-template-columns: 1fr;
        }
        .bulk-payment-status,
        .invoice-bulk-payment > .full:not(.actions),
        .invoice-bulk-payment > .full.actions { grid-column: auto; }
    }
</style>

<div class="invoice-index-page">
<div class="topbar">
    <div>
        <h1>Invoices</h1>
        <div class="muted">Monthly bills, product invoices, party dues, and draft review.</div>
        <div class="invoice-generate-note">
            Generate Bills creates monthly service invoices for special never-suspend customers only. Use Create Invoice for routers, installation, service charges, and other one-time bills.
        </div>
    </div>
  <div class="actions invoice-quick-actions">
        <a class="btn secondary" href="{{ route('invoices.create', ['type' => 'product']) }}">Create Invoice</a>
        <a class="btn light" href="{{ route('quotations.create') }}">Create Quotation</a>
        <a class="btn light" href="{{ route('invoices.create', ['type' => 'service']) }}">Service Charge</a>
    <form method="post" action="{{ route('invoices.generate') }}" class="actions invoice-generate-form">
            @csrf
            <input type="month" name="billing_month" value="{{ $generationPreviewMonth }}" required>
            <button class="btn" type="submit">Generate Bills</button>
        </form>
    </div>
</div>

<div class="grid stats invoice-stats">
    <section class="card invoice-stat">
        <span>Filtered Invoices</span>
        <strong>{{ number_format($invoiceSummary['total_count']) }}</strong>
        <small>Draft {{ number_format($invoiceSummary['draft_count']) }} / Final {{ number_format($invoiceSummary['final_count']) }}</small>
    </section>
    <section class="card invoice-stat">
        <span>Total Due</span>
        <strong>BDT {{ number_format($invoiceSummary['due_amount'], 2) }}</strong>
        <small>Unpaid {{ number_format($invoiceSummary['unpaid_count']) }} / Partial {{ number_format($invoiceSummary['partial_count']) }}</small>
    </section>
    <section class="card invoice-stat">
        <span>Total Billed</span>
        <strong>BDT {{ number_format($invoiceSummary['total_amount'], 2) }}</strong>
        <small>Paid {{ number_format($invoiceSummary['paid_count']) }} / Returned {{ number_format($invoiceSummary['returned_count']) }}</small>
    </section>
    <section class="card invoice-stat">
        <span>Advance Balance</span>
        <strong>BDT {{ number_format($invoiceSummary['advance_balance'], 2) }}</strong>
        <small>{{ number_format($generatePreviewCount) }} service bill(s) can still be generated for {{ $generationPreviewMonth }}.</small>
    </section>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full">
        <label>Search</label>
        <input name="search" value="{{ request('search') }}" placeholder="Party, mobile, connection ID, invoice no, product, serial, or month">
    </div>
    <div>
        <label>Billing Month</label>
        <input type="month" name="billing_month" value="{{ request('billing_month') }}">
    </div>
    <div>
        <label>Payment Status</label>
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['unpaid', 'partial', 'paid', 'returned'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Invoice Type</label>
        <select name="invoice_type">
            <option value="">All types</option>
            @foreach (['service', 'product'] as $type)
                <option value="{{ $type }}" @selected(request('invoice_type') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Final State</label>
        <select name="final_state">
            <option value="">All</option>
            <option value="draft" @selected(request('final_state') === 'draft')>Draft</option>
            <option value="final" @selected(request('final_state') === 'final')>Final</option>
        </select>
    </div>
    <div>
        <label>Minimum Due</label>
        <input type="number" step="0.01" min="0" name="min_due" value="{{ request('min_due') }}" placeholder="Example: 1000">
    </div>
    <div>
        <label>Due From</label>
        <input type="date" name="due_from" value="{{ request('due_from') }}">
    </div>
    <div>
        <label>Due To</label>
        <input type="date" name="due_to" value="{{ request('due_to') }}">
    </div>
    <div>
        <label>Quick Filter</label>
        <label class="filter-check">
            <input type="checkbox" name="due_only" value="1" @checked(request()->boolean('due_only'))>
            Due invoices only
        </label>
    </div>
    <div class="full actions">
        <button class="btn secondary" type="submit">Search</button>
        <a class="btn light" href="{{ route('invoices.index') }}">Reset</a>
        <a class="btn light" href="{{ route('invoices.index', ['due_only' => 1, 'status' => 'unpaid']) }}">Unpaid Due</a>
        <a class="btn light" href="{{ route('invoices.index', ['final_state' => 'draft']) }}">Drafts</a>
    </div>
</form>

@include('partials.per_page')

@if ($canFinalizeInvoices)
    <form method="post" action="{{ route('invoices.finalize-selected') }}" id="bulkFinalizeForm" onsubmit="return confirm('Finalize all selected draft invoices? You will not be able to edit them after finalizing.');">
        @csrf
        <div id="bulkFinalizeInvoiceIds"></div>
    </form>
@endif
@if ($canRecordPayments)
    <form method="post" action="{{ route('invoices.pay-selected') }}" id="bulkPaymentForm" onsubmit="return confirm('Record one payment for all selected due invoices?');">
        @csrf
        <div id="bulkPaymentInvoiceIds"></div>
    </form>
@endif
@if ($canBulkSelectInvoices)
    <div class="card invoice-bulk-actions">
        <div class="invoice-bulk-heading">
            <strong>Bulk actions</strong>
            <div class="muted">Select invoices from this list, then finalize drafts or record one payment for one party.</div>
        </div>
        <div class="actions invoice-selection-actions">
            <button class="btn light" type="button" id="selectAllInvoices">Select all</button>
            <button class="btn light" type="button" id="deselectAllInvoices">Deselect all</button>
            @if ($canFinalizeInvoices)
                <button class="btn" type="submit" form="bulkFinalizeForm" id="finalizeSelectedInvoices">Final all selected</button>
            @endif
        </div>
        @if ($canRecordPayments)
            <div class="invoice-bulk-payment">
                <div>
                    <label>Payment amount</label>
                    <input type="number" step="0.01" name="amount" id="bulkPaymentAmount" form="bulkPaymentForm" readonly required>
                </div>
                <div>
                    <label>Method</label>
                    <select name="payment_method" id="bulkPaymentMethod" form="bulkPaymentForm" required>
                        <option value="cash">Cash</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div id="bulkPaymentAccountWrap">
                    <label>Account</label>
                    <select name="payment_account_id" id="bulkPaymentAccount" form="bulkPaymentForm">
                        <option value="">Select account</option>
                    </select>
                </div>
                <div>
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" value="{{ now()->toDateString() }}" form="bulkPaymentForm" required>
                </div>
                <div class="bulk-payment-status" id="bulkPaymentStatus">Select due invoices from one party.</div>
                <div class="full">
                    <label>Note</label>
                    <textarea name="note" form="bulkPaymentForm">Bulk payment from selected invoices.</textarea>
                </div>
                <div class="full actions">
                    <button class="btn secondary" type="submit" form="bulkPaymentForm" id="bulkPaymentSubmit">Record payment</button>
                </div>
            </div>
        @endif
    </div>
@endif

<table class="invoice-table">
    <thead>
        <tr>
            @if ($canBulkSelectInvoices)
                <th class="invoice-select-cell">Select</th>
            @endif
            <th>Invoice</th>
            <th>Party</th>
            <th>Mobile</th>
            <th>Month</th>
            <th>Type</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Due</th>
            <th>Balance</th>
            <th>Status</th>
            <th>State</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    @forelse ($invoices as $invoice)
        @php
            $isOverdue = (float) $invoice->due_amount > 0 && $invoice->due_date && $invoice->due_date->isPast();
            $rowClass = $invoice->status === 'paid'
                ? 'invoice-row-paid'
                : ($isOverdue ? 'invoice-row-overdue' : ((float) $invoice->due_amount > 0 ? 'invoice-row-due' : ''));
        @endphp
        <tr class="{{ $rowClass }}" data-href="{{ route('invoices.show', $invoice) }}">
            @if ($canBulkSelectInvoices)
                <td class="invoice-select-cell">
                    @if (! $invoice->isFinalized() || (float) $invoice->due_amount > 0)
                        <input
                            type="checkbox"
                            value="{{ $invoice->id }}"
                            class="invoice-select"
                            data-customer-id="{{ $invoice->customer_id }}"
                            data-due="{{ (float) $invoice->due_amount }}"
                            data-finalizable="{{ $invoice->isFinalized() ? '0' : '1' }}"
                            @checked(request('final_state') === 'draft')
                        >
                    @else
                        <span class="muted">-</span>
                    @endif
                </td>
            @endif
            <td>
                <a href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->invoice_no }}</strong></a>
                @if ($isOverdue)
                    <div style="margin-top:5px"><span class="badge overdue">Overdue</span></div>
                @endif
            </td>
            <td>{{ $invoice->customer->name }}</td>
            <td>{{ $invoice->customer->phone }}</td>
            <td>{{ $invoice->formatted_billing_month }}</td>
            <td>{{ ucfirst($invoice->invoice_type ?? 'service') }}</td>
            <td>{{ number_format($invoice->total, 2) }}</td>
            <td>{{ number_format($invoice->paid_amount, 2) }}</td>
            <td>{{ number_format($invoice->due_amount, 2) }}</td>
            <td>{{ number_format($invoice->customer->account_balance, 2) }}</td>
            <td><span class="badge {{ $invoice->status }}">{{ $invoice->status }}</span></td>
            <td>
                @if ($invoice->isFinalized())
                    <span class="badge final">Final</span>
                @else
                    <span class="badge draft">Draft</span>
                @endif
            </td>
            <td class="invoice-row-actions">
                <div class="actions">
                    <a class="btn light" href="{{ route('invoices.show', $invoice) }}">View</a>
                    @if (! $invoice->isFinalized())
                        <a class="btn secondary" href="{{ route('invoices.edit', $invoice) }}">Edit</a>
                        @if ($canFinalizeInvoices)
                            <form method="post" action="{{ route('invoices.finalize', $invoice) }}" onsubmit="return confirm('Finalize {{ $invoice->invoice_no }}? You will not be able to edit it after finalizing.');">
                                @csrf
                                <button class="btn" type="submit">Final</button>
                            </form>
                        @endif
                    @endif
                    @if ($canRecordPayments && (float) $invoice->due_amount > 0)
                        <a class="btn" href="{{ route('invoices.show', $invoice) }}#record-payment">Payment</a>
                    @endif
                    <details class="action-menu">
                        <summary class="btn light">More</summary>
                        <div class="action-menu-panel">
                            <a class="btn light" href="{{ route('invoices.invoice', $invoice) }}" target="_blank">Print Bill</a>
                            <a class="btn light" href="{{ route('invoices.delivery-challan', $invoice) }}" target="_blank">Challan</a>
                            @if ($canManageInvoices)
                                <form method="post" action="{{ route('invoices.copy-next-month', $invoice) }}" onsubmit="return confirm('Copy this invoice for next month with same items and prices?');">
                                    @csrf
                                    <button class="btn light" type="submit">Copy Next Month</button>
                                </form>
                            @endif
                        </div>
                    </details>
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="{{ $canBulkSelectInvoices ? 13 : 12 }}">No invoices found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $invoices->links() }}</div>
@if ($canBulkSelectInvoices)
    <script>
        const invoiceSelects = Array.from(document.querySelectorAll('.invoice-select'));
        const selectAllInvoices = document.getElementById('selectAllInvoices');
        const deselectAllInvoices = document.getElementById('deselectAllInvoices');
        const finalizeSelectedInvoices = document.getElementById('finalizeSelectedInvoices');
        const bulkFinalizeInvoiceIds = document.getElementById('bulkFinalizeInvoiceIds');
        const bulkPaymentInvoiceIds = document.getElementById('bulkPaymentInvoiceIds');
        const bulkPaymentAmount = document.getElementById('bulkPaymentAmount');
        const bulkPaymentSubmit = document.getElementById('bulkPaymentSubmit');
        const bulkPaymentStatus = document.getElementById('bulkPaymentStatus');
        const bulkPaymentMethod = document.getElementById('bulkPaymentMethod');
        const bulkPaymentAccount = document.getElementById('bulkPaymentAccount');
        const bulkPaymentAccountWrap = document.getElementById('bulkPaymentAccountWrap');
        const accountsByMethod = @json($accountsByMethod);

        function refreshFinalizeSelectedButton() {
            const selectedFinalizable = invoiceSelects.filter((input) => input.checked && input.dataset.finalizable === '1');

            if (bulkFinalizeInvoiceIds) {
                bulkFinalizeInvoiceIds.innerHTML = '';
                selectedFinalizable.forEach((input) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'invoice_ids[]';
                    hidden.value = input.value;
                    bulkFinalizeInvoiceIds.appendChild(hidden);
                });
            }

            if (! finalizeSelectedInvoices) {
                return;
            }

            finalizeSelectedInvoices.disabled = selectedFinalizable.length === 0;
            finalizeSelectedInvoices.textContent = selectedFinalizable.length > 0
                ? 'Final all selected (' + selectedFinalizable.length + ')'
                : 'Final all selected';
        }

        function refreshPaymentAccountOptions() {
            if (! bulkPaymentMethod || ! bulkPaymentAccount || ! bulkPaymentAccountWrap) {
                return;
            }

            const method = bulkPaymentMethod.value;
            const needsAccount = method !== 'cash';

            bulkPaymentAccountWrap.style.display = needsAccount ? 'block' : 'none';
            bulkPaymentAccount.required = needsAccount;
            bulkPaymentAccount.innerHTML = '<option value="">Select account</option>';

            if (! needsAccount) {
                bulkPaymentAccount.value = '';
                return;
            }

            (accountsByMethod[method] || []).forEach((account) => {
                const option = document.createElement('option');
                option.value = account.id;
                option.textContent = account.label;
                bulkPaymentAccount.appendChild(option);
            });
        }

        function refreshBulkPayment() {
            if (! bulkPaymentAmount || ! bulkPaymentSubmit || ! bulkPaymentStatus || ! bulkPaymentInvoiceIds) {
                return;
            }

            const selectedDue = invoiceSelects.filter((input) => input.checked && Number(input.dataset.due) > 0);
            const customerIds = Array.from(new Set(selectedDue.map((input) => input.dataset.customerId)));
            const dueTotal = selectedDue.reduce((total, input) => total + Number(input.dataset.due), 0);
            const isOneParty = customerIds.length === 1;

            bulkPaymentInvoiceIds.innerHTML = '';

            if (isOneParty) {
                selectedDue.forEach((input) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'invoice_ids[]';
                    hidden.value = input.value;
                    bulkPaymentInvoiceIds.appendChild(hidden);
                });
            }

            bulkPaymentAmount.value = dueTotal > 0 ? dueTotal.toFixed(2) : '';
            bulkPaymentSubmit.disabled = selectedDue.length === 0 || ! isOneParty;

            if (selectedDue.length === 0) {
                bulkPaymentStatus.textContent = 'Select due invoices from one party.';
            } else if (! isOneParty) {
                bulkPaymentStatus.textContent = 'Selected invoices must be from one party.';
            } else {
                bulkPaymentStatus.textContent = selectedDue.length + ' invoice(s), total ' + dueTotal.toFixed(2);
            }
        }

        function refreshBulkActions() {
            refreshFinalizeSelectedButton();
            refreshBulkPayment();
        }

        selectAllInvoices?.addEventListener('click', function () {
            invoiceSelects.forEach((input) => input.checked = true);
            refreshBulkActions();
        });

        deselectAllInvoices?.addEventListener('click', function () {
            invoiceSelects.forEach((input) => input.checked = false);
            refreshBulkActions();
        });

        invoiceSelects.forEach((input) => input.addEventListener('change', refreshBulkActions));
        bulkPaymentMethod?.addEventListener('change', refreshPaymentAccountOptions);
        refreshPaymentAccountOptions();
        refreshBulkActions();
    </script>
@endif
 </div>
@endsection
