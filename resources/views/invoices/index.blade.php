@extends('layouts.app')

@section('content')
@php
    $canManageInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canFinalizeInvoices = auth()->user()?->hasPermission('finalize_invoices');
    $canRecordPayments = auth()->user()?->hasPermission('manage_payments');
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

    .invoice-bulk-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
        padding: 12px;
    }

    .invoice-bulk-actions .actions {
        justify-content: flex-end;
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
        .invoice-row-actions {
            min-width: 210px;
        }

        .invoice-bulk-actions {
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>

<div class="topbar">
    <div>
        <h1>Invoices</h1>
        <div class="muted">Monthly bills, product invoices, party dues, and draft review.</div>
        <div class="invoice-generate-note">
            Generate Bills creates monthly service invoices for special never-suspend customers only. Use Create Invoice for routers, installation, service charges, and other one-time bills.
        </div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('invoices.create', ['type' => 'product']) }}">Create Invoice</a>
        <a class="btn light" href="{{ route('quotations.create') }}">Create Quotation</a>
        <a class="btn light" href="{{ route('invoices.create', ['type' => 'service']) }}">Service Charge</a>
        <form method="post" action="{{ route('invoices.generate') }}" class="actions">
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
        <small>Paid invoices {{ number_format($invoiceSummary['paid_count']) }}</small>
    </section>
    <section class="card invoice-stat">
        <span>Advance Balance</span>
        <strong>BDT {{ number_format($invoiceSummary['advance_balance'], 2) }}</strong>
        <small>{{ number_format($generatePreviewCount) }} service bill(s) can still be generated for {{ $generationPreviewMonth }}.</small>
    </section>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div class="full">
        <label>Search</label>
        <input name="search" value="{{ request('search') }}" placeholder="Party name, mobile, connection ID, invoice no, or month">
    </div>
    <div>
        <label>Billing Month</label>
        <input type="month" name="billing_month" value="{{ request('billing_month') }}">
    </div>
    <div>
        <label>Payment Status</label>
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['unpaid', 'partial', 'paid'] as $status)
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
    </form>
    <div class="card invoice-bulk-actions">
        <div>
            <strong>Bulk Final</strong>
            <div class="muted">Select draft invoices from this list, then finalize all selected together.</div>
        </div>
        <div class="actions">
            <button class="btn light" type="button" id="selectAllInvoices">Select all</button>
            <button class="btn light" type="button" id="deselectAllInvoices">Deselect all</button>
            <button class="btn" type="submit" form="bulkFinalizeForm" id="finalizeSelectedInvoices">Final all selected</button>
        </div>
    </div>
@endif

<table class="invoice-table">
    <thead>
        <tr>
            @if ($canFinalizeInvoices)
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
            @if ($canFinalizeInvoices)
                <td class="invoice-select-cell">
                    @if (! $invoice->isFinalized())
                        <input
                            type="checkbox"
                            name="invoice_ids[]"
                            value="{{ $invoice->id }}"
                            form="bulkFinalizeForm"
                            class="invoice-select"
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
        <tr><td colspan="{{ $canFinalizeInvoices ? 13 : 12 }}">No invoices found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $invoices->links() }}</div>
@if ($canFinalizeInvoices)
    <script>
        const invoiceSelects = Array.from(document.querySelectorAll('.invoice-select'));
        const selectAllInvoices = document.getElementById('selectAllInvoices');
        const deselectAllInvoices = document.getElementById('deselectAllInvoices');
        const finalizeSelectedInvoices = document.getElementById('finalizeSelectedInvoices');

        function refreshFinalizeSelectedButton() {
            const selectedCount = invoiceSelects.filter((input) => input.checked).length;
            finalizeSelectedInvoices.disabled = selectedCount === 0;
            finalizeSelectedInvoices.textContent = selectedCount > 0
                ? 'Final all selected (' + selectedCount + ')'
                : 'Final all selected';
        }

        selectAllInvoices?.addEventListener('click', function () {
            invoiceSelects.forEach((input) => input.checked = true);
            refreshFinalizeSelectedButton();
        });

        deselectAllInvoices?.addEventListener('click', function () {
            invoiceSelects.forEach((input) => input.checked = false);
            refreshFinalizeSelectedButton();
        });

        invoiceSelects.forEach((input) => input.addEventListener('change', refreshFinalizeSelectedButton));
        refreshFinalizeSelectedButton();
    </script>
@endif
@endsection
