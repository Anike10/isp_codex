@extends('layouts.app')

@section('content')
@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $accountsByMethod = $paymentAccounts
        ->groupBy('payment_method')
        ->map(fn ($accounts) => $accounts->map(fn ($account) => [
            'id' => $account->id,
            'label' => $account->account_name.' - '.$account->account_number,
        ])->values())
        ->toArray();
    $totalDue = (float) $dueInvoices->sum('due_amount');
    $advanceBalance = (float) $customer->account_balance;
    $netBalance = $advanceBalance - $totalDue;
    $selectedPaymentMethod = old('payment_method', $paymentDefault['payment_method'] ?? 'cash');
    $selectedPaymentAccountId = old('payment_account_id', $paymentDefault['payment_account_id'] ?? null);
    $oldAllocations = old('invoice_allocations', []);
    $hasOldAllocations = collect($oldAllocations)->filter(static fn ($amount) => (float) $amount > 0)->isNotEmpty();
    $amountDefault = old(
        'amount',
        $totalDue > 0 ? number_format($totalDue, 2, '.', '') : ''
    );
@endphp

<style>
    .payment-page {
        --pp-ink: var(--ink);
        --pp-muted: var(--muted);
        --pp-line: #e4e9f2;
        --pp-line-soft: #eef2f8;
        --pp-surface: #fff;
        --pp-raise: #f8fafd;
        --pp-accent: var(--accent);
        --pp-good: #0e9f6e;
        --pp-warn: #c2410c;
        --pp-danger: var(--danger);
        --pp-radius: 16px;
        --pp-radius-sm: 10px;
        --pp-shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 1px 3px rgba(16, 24, 40, .08);
        display: flex;
        flex-direction: column;
        gap: 18px;
        color: var(--pp-ink);
        font-size: 14px;
    }
    .payment-page .page-help { display: none; }

    /* Header ------------------------------------------------------------ */
    .pp-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .pp-head h1 {
        margin: 0;
        font-size: 24px;
        letter-spacing: -0.02em;
    }
    .pp-head .pp-sub {
        margin: 6px 0 0;
        color: var(--pp-muted);
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .pp-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--pp-raise);
        border: 1px solid var(--pp-line);
        font-weight: 700;
        font-size: 12px;
        color: #334155;
    }
    .pp-head-actions { display: inline-flex; gap: 8px; align-items: center; }
    .pp-icon-btn {
        width: 34px;
        height: 34px;
        border: 1px solid var(--pp-line);
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: #475467;
        font-weight: 800;
        font-size: 13px;
        cursor: help;
        text-decoration: none;
        flex: none;
    }

    /* Summary tiles --------------------------------------------------- */
    .pp-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }
    .pp-tile {
        border: 1px solid var(--pp-line);
        border-radius: 14px;
        background: var(--pp-surface);
        padding: 14px 16px;
        box-shadow: var(--pp-shadow);
        position: relative;
        overflow: hidden;
    }
    .pp-tile::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 3px;
        background: var(--pp-line);
    }
    .pp-tile.is-due::before { background: var(--pp-warn); }
    .pp-tile.is-advance::before { background: var(--pp-accent); }
    .pp-tile.is-net::before { background: #6366f1; }
    .pp-tile.is-after {
        border-color: rgba(29, 118, 201, .45);
        background: linear-gradient(180deg, #f5faff, #fff);
    }
    .pp-tile.is-after::before { background: var(--pp-accent); }
    .pp-tile .pp-tile-label {
        color: var(--pp-muted);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 6px;
    }
    .pp-tile .pp-tile-value {
        font-size: 22px;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.01em;
    }
    .pp-tile .pp-tile-hint {
        margin-top: 4px;
        font-size: 11px;
        color: var(--pp-muted);
    }

    /* Layout -------------------------------------------------------------- */
    .pp-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    .pp-card {
        border: 1px solid var(--pp-line);
        border-radius: var(--pp-radius);
        background: var(--pp-surface);
        box-shadow: var(--pp-shadow);
        overflow: hidden;
    }
    .pp-card__head {
        padding: 16px 18px;
        border-bottom: 1px solid var(--pp-line-soft);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .pp-card__head h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .pp-card__head .pp-help {
        font-size: 12px;
        color: var(--pp-muted);
        font-weight: 500;
    }
    .pp-card__body { padding: 18px; }

    /* Keep as advance callout ----------------------------------------- */
    .pp-toggle-row {
        border: 1px solid var(--pp-line);
        border-radius: 12px;
        padding: 12px 14px;
        background: var(--pp-raise);
        display: flex;
        gap: 12px;
        align-items: flex-start;
        cursor: pointer;
        transition: border-color .15s ease, background .15s ease;
    }
    .pp-toggle-row:has(input:checked) {
        border-color: rgba(29, 118, 201, .55);
        background: #f2f8ff;
    }
    .pp-toggle-row input {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        accent-color: var(--pp-accent);
        flex: none;
    }
    .pp-toggle-row .pp-toggle-text strong { display: block; font-size: 13px; }
    .pp-toggle-row .pp-toggle-text span { color: var(--pp-muted); font-size: 12px; }

    /* Fields ----------------------------------------------------------- */
    .pp-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 14px;
    }
    .pp-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .pp-field.full { grid-column: 1 / -1; }
    .pp-field > label {
        font-weight: 700;
        font-size: 12.5px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
        color: #344054;
    }
    .pp-field > label .pp-tip {
        color: var(--pp-muted);
        font-weight: 500;
        cursor: help;
    }
    .pp-field input,
    .pp-field select,
    .pp-field textarea {
        width: 100%;
        border: 1px solid var(--pp-line);
        border-radius: var(--pp-radius-sm);
        background: #fff;
        padding: 10px 12px;
        min-height: 42px;
        font: inherit;
        color: var(--pp-ink);
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pp-field input:focus,
    .pp-field select:focus,
    .pp-field textarea:focus {
        outline: none;
        border-color: var(--pp-accent);
        box-shadow: 0 0 0 4px rgba(29, 118, 201, .14);
    }
    .pp-field textarea { min-height: 70px; resize: vertical; }
    .pp-amount-wrap { position: relative; }
    .pp-amount-wrap::before {
        content: "৳";
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--pp-muted);
        font-weight: 800;
        font-size: 18px;
        pointer-events: none;
    }
    #amountInput {
        padding-left: 32px;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.01em;
        min-height: 50px;
    }
    .pp-field .pp-note { margin: 0; color: var(--pp-muted); font-size: 11.5px; }
    .pp-method-line { display: flex; align-items: flex-end; gap: 10px; }
    .pp-method-line select { flex: 1; }
    .pp-method-line .pp-default { flex: none; padding-bottom: 11px; }

    /* Preview block -------------------------------------------------------- */
    .pp-preview {
        margin-top: 16px;
        border: 1px dashed #c6d5e8;
        border-radius: 12px;
        background: #f7fbff;
        padding: 14px;
    }
    .pp-preview .pp-preview-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--pp-muted);
        margin-bottom: 10px;
    }
    .pp-preview-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }
    .pp-preview-cell {
        border: 1px solid #dbe6f3;
        border-radius: var(--pp-radius-sm);
        padding: 8px 10px;
        background: #fff;
    }
    .pp-preview-cell .k {
        color: var(--pp-muted);
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .pp-preview-cell .v { font-size: 15px; font-weight: 800; margin-top: 2px; }
    #previewText { margin: 10px 0 0; font-size: 12px; color: var(--pp-muted); }

    /* Invoice allocation -------------------------------------------------- */
    .pp-alloc-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
        font-size: 13px;
    }
    .pp-alloc-toolbar strong { font-size: 13px; }
    .pp-selectall {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--pp-muted);
        font-weight: 600;
        cursor: pointer;
        user-select: none;
    }
    .pp-selectall input { width: 16px; height: 16px; min-height: 0; accent-color: var(--pp-accent); }
    .pp-table-scroll { overflow-x: auto; border: 1px solid var(--pp-line-soft); border-radius: 12px; }
    .pp-invoice-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 460px;
        background: #fff;
        border: 0;
    }
    .pp-invoice-table thead th {
        background: var(--pp-raise);
        color: var(--pp-muted);
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid var(--pp-line-soft);
    }
    .pp-invoice-table tbody td {
        padding: 10px;
        border-bottom: 1px solid var(--pp-line-soft);
        vertical-align: middle;
        background: #fff;
    }
    .pp-invoice-table tbody tr:last-child td { border-bottom: 0; }
    .pp-invoice-table tbody tr:hover td { background: #fbfdff; }
    .invoice-row.disabled td { opacity: .55; }
    .invoice-row .invoice-toggle { width: 16px; height: 16px; min-height: 0; accent-color: var(--pp-accent); }
    .invoice-row .allocation-amount {
        width: 118px;
        min-height: 36px;
        padding: 7px 10px;
        border: 1px solid var(--pp-line);
        border-radius: 8px;
        background: #fff;
        font: inherit;
        text-align: right;
        font-weight: 700;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .invoice-row .allocation-amount:focus {
        outline: none;
        border-color: var(--pp-accent);
        box-shadow: 0 0 0 4px rgba(29, 118, 201, .14);
    }
    .invoice-row .allocation-amount[readonly],
    .invoice-row .allocation-amount:disabled { background: var(--pp-raise); color: var(--pp-muted); }
    .pp-inv-no { font-weight: 700; }
    .pp-inv-meta { color: var(--pp-muted); font-size: 11.5px; margin-top: 2px; }
    .pp-alloc-empty {
        color: var(--pp-muted);
        font-size: 13px;
        padding: 20px;
        text-align: center;
        border: 1px dashed var(--pp-line);
        border-radius: 12px;
        background: var(--pp-raise);
    }

    /* Footer / submit --------------------------------------------------- */
    .pp-formfoot {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        border-top: 1px solid var(--pp-line-soft);
        background: var(--pp-raise);
        flex-wrap: wrap;
    }
    .pp-formfoot .pp-foot-hint { margin-right: auto; color: var(--pp-muted); font-size: 12px; }
    #paymentSubmit { min-width: 172px; justify-content: center; font-size: 14px; min-height: 44px; border-radius: var(--pp-radius-sm); }

    /* History --------------------------------------------------------- */
    .pp-history .pp-card__body { padding: 0; }
    .pp-history table { border: 0; border-radius: 0; }
    .pp-history thead th {
        background: var(--pp-raise);
        text-transform: uppercase;
        font-size: 10.5px;
        letter-spacing: .4px;
        color: var(--pp-muted);
    }
    .pp-history th, .pp-history td { padding: 11px 14px; border-bottom: 1px solid var(--pp-line-soft); }
    .pp-history tbody tr:last-child td { border-bottom: 0; }
    .pp-history .badge { text-transform: capitalize; }

    @media (max-width: 1040px) {
        .pp-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 820px) {
        .pp-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 560px) {
        .pp-summary { grid-template-columns: 1fr; }
        .pp-fields { grid-template-columns: 1fr; }
        .pp-preview-grid { grid-template-columns: 1fr; }
        .pp-formfoot { position: sticky; bottom: 0; }
        #paymentSubmit { width: 100%; }
    }
</style>

<div class="payment-page">
    <header class="pp-head">
        <div>
            <h1>Record Party Payment</h1>
            <p class="pp-sub">
                <span class="pp-chip">{{ $customer->name }}</span>
                @if ($customer->connection_id)
                    <span class="pp-chip">ID {{ $customer->connection_id }}</span>
                @endif
            </p>
        </div>
        <div class="pp-head-actions">
            <a class="pp-icon-btn" href="javascript:void(0);" title="Choose the payment method/account, type the amount, then tick the invoices to apply it to. Untick an invoice to skip it — the leftover stays as advance.">?</a>
            <a class="btn light" href="{{ route('customers.show', $customer) }}">Back to party</a>
        </div>
    </header>

    <section class="pp-summary">
        <article class="pp-tile is-due">
            <div class="pp-tile-label">Running total due</div>
            <div class="pp-tile-value" id="summaryDue">{{ number_format($totalDue, 2) }}</div>
        </article>
        <article class="pp-tile is-advance">
            <div class="pp-tile-label">Advance balance</div>
            <div class="pp-tile-value" id="summaryAdvance">{{ number_format($advanceBalance, 2) }}</div>
        </article>
        <article class="pp-tile is-net">
            <div class="pp-tile-label">Net balance</div>
            <div class="pp-tile-value" id="summaryNet">{{ number_format($netBalance, 2) }}</div>
        </article>
        <article class="pp-tile is-after">
            <div class="pp-tile-label">After payment preview</div>
            <div class="pp-tile-value" id="summaryAfter">{{ number_format($netBalance, 2) }}</div>
            <div class="pp-tile-hint">Updates live as you type</div>
        </article>
    </section>

    <form method="post" action="{{ route('customers.payments.store', $customer) }}" id="paymentForm">
        @csrf

        <div class="pp-grid">
            <section class="pp-card">
                <div class="pp-card__head">
                    <h2>Payment details</h2>
                    <span class="pp-help">Amount + allocation are fast</span>
                </div>
                <div class="pp-card__body">
                    <label class="pp-toggle-row">
                        <input type="checkbox" id="keepAsAdvance" name="keep_as_advance" value="1" @checked(old('keep_as_advance') === '1')>
                        <span class="pp-toggle-text">
                            <strong>Keep this payment as advance</strong>
                            <span>Enable when this money should not reduce any invoice automatically.</span>
                        </span>
                    </label>

                    <div class="pp-fields">
                        <div class="pp-field full">
                            <label for="amountInput">
                                Amount
                                <span class="pp-tip" title="Used for invoice allocations first; anything left over stays as advance.">&#9432;</span>
                            </label>
                            <div class="pp-amount-wrap">
                                <input
                                    id="amountInput"
                                    name="amount"
                                    type="text" inputmode="decimal"
                                    autocomplete="off"
                                    value="{{ $amountDefault }}"
                                    placeholder="0.00"
                                    required
                                >
                            </div>
                            <p class="pp-note">Paying several invoices at once? Type the total amount once. No mouse-wheel or arrow-step adjustment.</p>
                        </div>

                        <div class="pp-field">
                            <label for="paymentDate">Payment date</label>
                            <input id="paymentDate" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" required>
                        </div>

                        <div class="pp-field">
                            <label for="paymentMethod">Payment method</label>
                            <div class="pp-method-line">
                                <select id="paymentMethod" name="payment_method" required>
                                    <option value="cash" @selected($selectedPaymentMethod === 'cash')>Cash</option>
                                    <option value="bkash" @selected($selectedPaymentMethod === 'bkash')>bKash</option>
                                    <option value="nagad" @selected($selectedPaymentMethod === 'nagad')>Nagad</option>
                                    <option value="bank" @selected($selectedPaymentMethod === 'bank')>Bank</option>
                                </select>
                                <span class="pp-default">@include('partials.payment_default_checkbox')</span>
                            </div>
                        </div>

                        <div class="pp-field" id="accountSelectWrap">
                            <label for="paymentAccount">Account</label>
                            <select id="paymentAccount" name="payment_account_id">
                                <option value="">Select account</option>
                            </select>
                        </div>

                        <div class="pp-field">
                            <label for="referenceInput">Reference</label>
                            <input id="referenceInput" type="text" name="reference" value="{{ old('reference') }}" placeholder="Transaction ID / receipt no">
                        </div>

                        <div class="pp-field full">
                            <label for="noteInput">Note</label>
                            <textarea id="noteInput" name="note" rows="2" placeholder="Optional note">{{ old('note') }}</textarea>
                        </div>
                    </div>

                    <div class="pp-preview">
                        <div class="pp-preview-title">After payment preview</div>
                        <div class="pp-preview-grid">
                            <div class="pp-preview-cell">
                                <div class="k">Due remaining</div>
                                <div class="v" id="previewDue">0.00</div>
                            </div>
                            <div class="pp-preview-cell">
                                <div class="k">Advance after</div>
                                <div class="v" id="previewAdvance">0.00</div>
                            </div>
                            <div class="pp-preview-cell">
                                <div class="k">Net balance</div>
                                <div class="v" id="previewNet">0.00</div>
                            </div>
                        </div>
                        <p id="previewText">Enter amount to preview invoice and balance impact.</p>
                    </div>
                </div>
            </section>

            <section class="pp-card">
                <div class="pp-card__head">
                    <h2>Invoice allocation</h2>
                    <a class="pp-icon-btn" href="javascript:void(0);" title="Tick the invoices this payment should pay. An unticked invoice is not reduced.">?</a>
                </div>
                <div class="pp-card__body">
                    @if ($dueInvoices->isNotEmpty())
                        <div class="pp-alloc-toolbar">
                            <strong>Outstanding invoices ({{ $dueInvoices->count() }})</strong>
                            <label class="pp-selectall">
                                <input id="toggleAllInvoices" type="checkbox" checked>
                                <span>Select all</span>
                            </label>
                        </div>
                        <div class="pp-table-scroll">
                            <table class="pp-invoice-table">
                                <thead>
                                    <tr>
                                        <th style="width:44px;">Use</th>
                                        <th>Invoice</th>
                                        <th>Due amount</th>
                                        <th style="width:150px;">Apply from this payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dueInvoices as $invoice)
                                        @php
                                            $defaultAllocation = (float) old('invoice_allocations.'.$invoice->id, 0);
                                        @endphp
                                        <tr
                                            class="invoice-row"
                                            data-id="{{ $invoice->id }}"
                                            data-due="{{ $invoice->due_amount }}"
                                        >
                                            <td>
                                                <input
                                                    class="invoice-toggle"
                                                    type="checkbox"
                                                    value="1"
                                                    checked
                                                    data-id="{{ $invoice->id }}"
                                                    aria-label="Use {{ $invoice->invoice_no }}"
                                                >
                                            </td>
                                            <td>
                                                <div class="pp-inv-no">
                                                    @if ($canOpenInvoices)
                                                        <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                                                    @else
                                                        {{ $invoice->invoice_no }}
                                                    @endif
                                                </div>
                                                <div class="pp-inv-meta">{{ $invoice->formatted_billing_month }} &bull; {{ $invoice->due_date?->format('d/m/Y') ?? 'No due date' }}</div>
                                            </td>
                                            <td>{{ number_format($invoice->due_amount, 2) }}</td>
                                            <td>
                                                <input
                                                    class="allocation-amount"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $invoice->due_amount }}"
                                                    name="invoice_allocations[{{ $invoice->id }}]"
                                                    value="{{ $defaultAllocation > 0 ? number_format($defaultAllocation, 2, '.', '') : '' }}"
                                                    placeholder="0.00"
                                                    aria-label="Allocation amount for {{ $invoice->invoice_no }}"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="pp-alloc-empty">No outstanding invoices found. The full amount will be saved as advance.</p>
                    @endif
                </div>
            </section>
        </div>

        <div class="pp-card" style="margin-top:16px;">
            <div class="pp-formfoot">
                <span class="pp-foot-hint">Review the preview above, then submit.</span>
                <button id="paymentSubmit" class="btn" type="submit">Submit Payment</button>
            </div>
        </div>
    </form>

    <section class="pp-card pp-history">
        <div class="pp-card__head">
            <h2>Advance balance history</h2>
            <span class="pp-help">Last {{ $balanceTransactions->count() }} entr{{ $balanceTransactions->count() === 1 ? 'y' : 'ies' }}</span>
        </div>
        <div class="pp-card__body">
            <div class="table-scroll" style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Reference</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($balanceTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge {{ $transaction->direction === 'credit' ? 'active' : 'due' }}">
                                        {{ $transaction->direction }}
                                    </span>
                                </td>
                                <td>{{ number_format($transaction->amount, 2) }}</td>
                                <td>{{ number_format($transaction->balance_after, 2) }}</td>
                                <td>{{ $transaction->reference ?? 'N/A' }}</td>
                                <td>{{ $transaction->note ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No advance balance history yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<script>
const paymentForm = document.getElementById('paymentForm');
const keepAsAdvance = document.getElementById('keepAsAdvance');
const amountInput = document.getElementById('amountInput');
const paymentMethod = document.getElementById('paymentMethod');
const accountWrap = document.getElementById('accountSelectWrap');
const paymentAccount = document.getElementById('paymentAccount');
const toggleAllInvoices = document.getElementById('toggleAllInvoices');
const invoiceRows = [...document.querySelectorAll('.invoice-row')];
    const paymentSubmit = document.getElementById('paymentSubmit');
    const summaryAfter = document.getElementById('summaryAfter');
const summaryDue = document.getElementById('summaryDue');
const summaryAdvance = document.getElementById('summaryAdvance');
const summaryNet = document.getElementById('summaryNet');
    const previewDue = document.getElementById('previewDue');
    const previewAdvance = document.getElementById('previewAdvance');
    const previewNet = document.getElementById('previewNet');
    const previewText = document.getElementById('previewText');

const accountsByMethod = @json($accountsByMethod);
const oldAccountId = @json($selectedPaymentAccountId);
const totalDue = Number(@json($totalDue));
const currentAdvance = Number(@json($advanceBalance));
const paymentUrl = @json(route('customers.payments.store', $customer));
const advanceUrl = @json(route('customers.advance-payments.store', $customer));
const hasOldAllocations = @json($hasOldAllocations);
let manualAllocation = hasOldAllocations;

const rowsState = invoiceRows.map((row) => {
    return {
        row,
        id: Number(row.dataset.id),
        due: Number(row.dataset.due || 0),
        toggle: row.querySelector('.invoice-toggle'),
        input: row.querySelector('.allocation-amount'),
    };
});

function money(value) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

function parseMoney(value) {
    const parsed = Number(String(value).replace(/,/g, ''));
    return Number.isFinite(parsed) ? parsed : 0;
}

function updateAccountOptions() {
    const method = paymentMethod.value;
    const shouldShow = method !== 'cash';
    accountWrap.style.display = shouldShow ? 'block' : 'none';
    paymentAccount.required = shouldShow;
    paymentAccount.innerHTML = '<option value=\"\">Select account</option>';

    if (! shouldShow) {
        paymentAccount.value = '';
        return;
    }

    (accountsByMethod[method] || []).forEach((account) => {
        const option = document.createElement('option');
        option.value = account.id;
        option.textContent = account.label;
        option.selected = String(oldAccountId) === String(account.id);
        paymentAccount.appendChild(option);
    });
}

function normalizeAllocationInput(input) {
    const value = parseMoney(input.value);
    if (!Number.isFinite(value)) {
        input.value = '0';
        return 0;
    }

    const max = parseMoney(input.getAttribute('max'));
    const clamped = Math.max(0, Math.min(value, max));
    input.value = clamped.toFixed(2);
    return clamped;
}

function setRowState() {
    rowsState.forEach((state) => {
        const isActive = state.toggle.checked;
        state.row.classList.toggle('disabled', !isActive);
        state.input.disabled = !isActive || keepAsAdvance.checked;
        if (!isActive) {
            state.input.value = '0';
        }
    });
}

function setAllAllocationAmounts(allocation = 0) {
    rowsState.forEach((state) => {
        if (!state.toggle.checked) {
            return;
        }
        state.input.value = allocation > 0
            ? Number(allocation).toFixed(2)
            : '0';
    });
}

function autoDistribute(amount) {
    let remaining = amount;
    rowsState.forEach((state) => {
        if (!state.toggle.checked) {
            return;
        }
        const payable = Math.max(0, Math.min(state.due, remaining));
        state.input.value = payable > 0 ? payable.toFixed(2) : '0';
        remaining = Math.max(0, remaining - payable);
    });
    rowsState.filter((state) => state.toggle.checked === false)
        .forEach((state) => {
            state.input.value = '0';
        });
}

function enforceManualLimits(totalAmount) {
    rowsState.forEach((state) => {
        if (!state.toggle.checked) {
            state.input.value = '0';
            return;
        }
        const normalized = normalizeAllocationInput(state.input);
        if (normalized > state.due) {
            state.input.value = state.due.toFixed(2);
        }
    });

    const selectedStates = rowsState.filter((state) => state.toggle.checked);
    if (selectedStates.length === 0) {
        return;
    }

    let allocated = selectedStates.reduce((sum, state) => sum + parseMoney(state.input.value), 0);
    if (allocated <= totalAmount) {
        return;
    }

    let excess = allocated - totalAmount;
    for (let i = selectedStates.length - 1; i >= 0 && excess > 0; i -= 1) {
        const state = selectedStates[i];
        const current = parseMoney(state.input.value);
        const remove = Math.min(current, excess);
        const next = Math.max(0, current - remove);
        state.input.value = next.toFixed(2);
        excess -= remove;
    }
}

    function refreshAllocation() {
        const amount = parseMoney(amountInput.value);
        let allocatedTotal = 0;

    if (keepAsAdvance.checked) {
        setRowState();
        setAllAllocationAmounts(0);
    } else if (rowsState.length === 0) {
        // no invoice due
    } else if (!manualAllocation) {
        autoDistribute(amount);
    } else {
        enforceManualLimits(amount);
    }

    setRowState();
    if (!keepAsAdvance.checked) {
        allocatedTotal = rowsState.reduce((sum, state) => {
            return sum + parseMoney(state.input.value);
        }, 0);
    }

        const dueAfter = Math.max(0, totalDue - allocatedTotal);
        const advanceAfter = currentAdvance + amount - allocatedTotal;
        const netAfter = advanceAfter - dueAfter;
        const shouldTreatAsAdvance = keepAsAdvance.checked;

        summaryAfter.textContent = money(netAfter);
        previewDue.textContent = money(dueAfter);
        previewAdvance.textContent = money(advanceAfter);
        previewNet.textContent = money(netAfter);

    if (amount <= 0) {
        previewText.textContent = 'Enter amount to preview invoice and balance impact.';
    } else if (keepAsAdvance.checked) {
        previewText.textContent = `Keep as advance mode: total payment ${money(amount)} will be added to advance now.`;
    } else {
        previewText.textContent = `Allocated ${money(allocatedTotal)} to selected invoices; remaining ${money(advanceAfter - currentAdvance)} will be kept as advance.`;
    }

        paymentForm.action = shouldTreatAsAdvance ? advanceUrl : paymentUrl;
        paymentSubmit.textContent = shouldTreatAsAdvance ? 'Save As Advance' : 'Submit Payment';
}

function applyToggleAllState() {
    const allSelected = toggleAllInvoices?.checked ?? true;
    rowsState.forEach((state) => {
        state.toggle.checked = allSelected;
    });
    manualAllocation = hasOldAllocations;
    refreshAllocation();
}

paymentMethod.addEventListener('change', () => {
    updateAccountOptions();
    refreshAllocation();
});
amountInput.addEventListener('input', () => {
    if (!keepAsAdvance.checked && !manualAllocation) {
        autoDistribute(parseMoney(amountInput.value));
    }
    refreshAllocation();
});
keepAsAdvance.addEventListener('change', () => {
    manualAllocation = manualAllocation || keepAsAdvance.checked;
    refreshAllocation();
});
rowsState.forEach((state) => {
    state.toggle.addEventListener('change', () => {
        manualAllocation = true;
        refreshAllocation();
        if (toggleAllInvoices) {
            const allChecked = rowsState.every((rowState) => rowState.toggle.checked);
            toggleAllInvoices.checked = allChecked;
        }
    });
    state.input.addEventListener('input', () => {
        manualAllocation = true;
        refreshAllocation();
    });
});

if (toggleAllInvoices) {
    toggleAllInvoices.addEventListener('change', applyToggleAllState);
}

updateAccountOptions();
summaryDue.textContent = money(totalDue);
summaryAdvance.textContent = money(currentAdvance);
summaryNet.textContent = money(currentAdvance - totalDue);

if (rowsState.length > 0) {
    refreshAllocation();
} else {
    refreshAllocation();
}
</script>
@endsection
