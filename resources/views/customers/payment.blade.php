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
    $methodOptions = ['cash' => 'Cash', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank'];
@endphp

<style>
    .pp {
        --pp-ink: #101828;
        --pp-ink-soft: #344054;
        --pp-muted: #667085;
        --pp-border: #e4e8ef;
        --pp-hair: #eef1f5;
        --pp-raise: #f7f9fc;
        --pp-surface: #ffffff;
        --pp-accent: #1d76c9;
        --pp-accent-soft: #eef6fd;
        --pp-brand: #0f7a55;
        --pp-owed: #d92d20;
        --pp-shadow-sm: 0 1px 2px rgba(16, 24, 40, .05);
        --pp-shadow-md: 0 1px 2px rgba(16, 24, 40, .04), 0 12px 28px -14px rgba(16, 24, 40, .18);
        --pp-shadow-float: 0 -1px 0 rgba(16, 24, 40, .02), 0 18px 40px -12px rgba(16, 24, 40, .28);

        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: var(--pp-ink);
        display: flex;
        flex-direction: column;
        gap: 16px;
        font-size: 14px;
        line-height: 1.45;
    }
    .pp .num { font-variant-numeric: tabular-nums; }

    /* Header ---------------------------------------------------------- */
    .pp-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px 20px;
        flex-wrap: wrap;
    }
    .pp-top h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; }
    .pp-tags { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
    .pp-tag {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--pp-raise);
        border: 1px solid var(--pp-border);
        font-size: 12px;
        font-weight: 700;
        color: var(--pp-ink-soft);
    }
    .pp-back {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border: 1px solid var(--pp-border);
        border-radius: 10px;
        background: #fff;
        color: var(--pp-ink-soft);
        font-weight: 700;
        font-size: 13px;
        white-space: nowrap;
    }
    .pp-back:hover { background: var(--pp-raise); }

    /* Balance strip ------------------------------------------------------ */
    .pp-balance {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr)) 1.2fr;
        background: var(--pp-surface);
        border: 1px solid var(--pp-border);
        border-radius: 16px;
        padding: 16px 22px;
        box-shadow: var(--pp-shadow-sm);
    }
    .pp-bal { display: flex; flex-direction: column; gap: 4px; padding: 2px 20px; }
    .pp-bal + .pp-bal { border-left: 1px solid var(--pp-hair); }
    .pp-bal:first-child { padding-left: 0; }
    .pp-bal .k {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--pp-muted);
    }
    .pp-bal .v { font-size: 20px; font-weight: 800; letter-spacing: -0.01em; }
    .pp-bal.is-due .v { color: var(--pp-owed); }
    .pp-bal.is-after .k { color: var(--pp-accent); }
    .pp-bal.is-after .v { font-size: 23px; color: var(--pp-accent); }

    /* Layout ------------------------------------------------------------- */
    .pp-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
        gap: 18px;
        align-items: start;
    }
    .pp-grid > * { min-width: 0; }
    .pp-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .pp-col > * { min-width: 0; }
    .pp-col--side { position: sticky; top: 72px; }
    .pp-grid > .pp-history { grid-column: 1 / -1; }
    .pp-card, .pp-card__body, .pp-fields, .pp-method { min-width: 0; }

    .pp-card {
        background: var(--pp-surface);
        border: 1px solid var(--pp-border);
        border-radius: 16px;
        box-shadow: var(--pp-shadow-sm);
    }
    .pp-card__head {
        padding: 14px 18px;
        border-bottom: 1px solid var(--pp-hair);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .pp-card__head h2 { margin: 0; font-size: 14px; font-weight: 800; letter-spacing: -0.01em; }
    .pp-card__head .hint { font-size: 12px; color: var(--pp-muted); font-weight: 600; }
    .pp-card__body { padding: 18px; }

    /* Amount hero ---------------------------------------------------- */
    .pp-amount {
        background: linear-gradient(180deg, #ffffff, #fbfdff);
        border: 1px solid var(--pp-border);
        border-radius: 18px;
        padding: 20px 22px 18px;
        box-shadow: var(--pp-shadow-md);
    }
    .pp-amount .lead {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--pp-muted);
    }
    .pp-amount-field { position: relative; margin-top: 6px; }
    .pp-amount-field .cur {
        position: absolute;
        left: 2px;
        top: 50%;
        transform: translateY(-54%);
        font-size: 26px;
        font-weight: 800;
        color: var(--pp-muted);
        pointer-events: none;
    }
    #amountInput {
        width: 100%;
        border: 0;
        border-bottom: 2px solid var(--pp-border);
        border-radius: 0;
        background: transparent;
        padding: 2px 2px 8px 32px;
        font: inherit;
        font-size: 42px;
        font-weight: 800;
        letter-spacing: -0.025em;
        color: var(--pp-ink);
        font-variant-numeric: tabular-nums;
        -moz-appearance: textfield;
    }
    #amountInput:focus { outline: none; border-bottom-color: var(--pp-accent); }
    #amountInput::placeholder { color: #c3ccd8; }
    .pp-amount .sub { margin: 10px 0 0; font-size: 12px; color: var(--pp-muted); }

    .pp-switch {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-top: 16px;
        padding: 12px 14px;
        border: 1px solid var(--pp-border);
        border-radius: 12px;
        background: var(--pp-raise);
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .pp-switch:has(input:checked) { border-color: rgba(29, 118, 201, .55); background: var(--pp-accent-soft); }
    .pp-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .pp-switch .track {
        width: 40px;
        height: 23px;
        border-radius: 999px;
        background: #cbd3df;
        position: relative;
        flex: none;
        margin-top: 1px;
        transition: background .18s ease;
    }
    .pp-switch .track::after {
        content: "";
        position: absolute;
        top: 2px;
        left: 2px;
        width: 19px;
        height: 19px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 3px rgba(16, 24, 40, .35);
        transition: transform .18s ease;
    }
    .pp-switch input:checked + .track { background: var(--pp-accent); }
    .pp-switch input:checked + .track::after { transform: translateX(17px); }
    .pp-switch input:focus-visible + .track { box-shadow: 0 0 0 4px rgba(29, 118, 201, .25); }
    .pp-switch .txt strong { display: block; font-size: 13px; }
    .pp-switch .txt span { color: var(--pp-muted); font-size: 12px; }

    /* Detail fields ------------------------------------------------------ */
    .pp-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 16px; }
    .pp-f { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .pp-f.full { grid-column: 1 / -1; }
    .pp-f > label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .4px;
        color: var(--pp-ink-soft);
        text-transform: uppercase;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .pp-f > label .opt { font-weight: 600; text-transform: none; color: var(--pp-muted); letter-spacing: 0; }
    .pp-f input,
    .pp-f select,
    .pp-f textarea {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        border: 1px solid var(--pp-border);
        border-radius: 10px;
        background: #fff;
        padding: 10px 12px;
        min-height: 42px;
        font: inherit;
        color: var(--pp-ink);
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pp-f select { text-overflow: ellipsis; }
    .pp-f input:focus,
    .pp-f select:focus,
    .pp-f textarea:focus {
        outline: none;
        border-color: var(--pp-accent);
        box-shadow: 0 0 0 4px rgba(29, 118, 201, .13);
    }

    .pp-method { grid-column: 1 / -1; display: flex; flex-direction: column; gap: 8px; }
    .pp-method-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .pp-method-top .pp-seg-label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: var(--pp-ink-soft);
        margin: 0;
    }
    .pp-seg {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 4px;
        padding: 4px;
        background: var(--pp-raise);
        border: 1px solid var(--pp-border);
        border-radius: 11px;
    }
    .pp-seg button {
        border: 0;
        background: transparent;
        padding: 9px 8px;
        border-radius: 8px;
        font: inherit;
        font-weight: 700;
        font-size: 13px;
        color: var(--pp-muted);
        cursor: pointer;
        transition: background .12s ease, color .12s ease, box-shadow .12s ease;
    }
    .pp-seg button:hover { color: var(--pp-ink-soft); }
    .pp-seg button[aria-pressed="true"] {
        background: #fff;
        color: var(--pp-ink);
        box-shadow: var(--pp-shadow-sm);
    }

    /* Invoice allocation ---------------------------------------------- */
    .pp-alloc-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }
    .pp-alloc-head strong { font-size: 12.5px; }
    .pp-selectall {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 700;
        color: var(--pp-muted);
        cursor: pointer;
        user-select: none;
    }
    .pp-selectall input { width: 15px; height: 15px; accent-color: var(--pp-accent); }
    .pp-inv-list { display: flex; flex-direction: column; gap: 8px; }
    .invoice-row {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 14px;
        padding: 11px 14px;
        border: 1px solid var(--pp-border);
        border-radius: 12px;
        background: #fff;
        transition: border-color .15s ease, background .15s ease;
    }
    .invoice-row:has(.invoice-toggle:checked) { border-color: rgba(29, 118, 201, .5); background: #f6faff; }
    .invoice-row.disabled { opacity: .5; }
    .invoice-row .invoice-toggle { width: 16px; height: 16px; accent-color: var(--pp-accent); cursor: pointer; }
    .pp-inv-name { font-weight: 700; font-size: 13px; }
    .pp-inv-name a { color: var(--pp-accent); }
    .pp-inv-meta { color: var(--pp-muted); font-size: 11.5px; margin-top: 2px; }
    .pp-inv-due { font-size: 12px; color: var(--pp-muted); white-space: nowrap; text-align: right; }
    .pp-inv-due b { display: block; color: var(--pp-ink-soft); font-weight: 800; font-size: 13px; }
    .invoice-row .allocation-amount {
        width: 120px;
        min-height: 36px;
        padding: 7px 10px;
        border: 1px solid var(--pp-border);
        border-radius: 8px;
        background: #fff;
        font: inherit;
        font-weight: 700;
        text-align: right;
        color: var(--pp-ink);
        font-variant-numeric: tabular-nums;
        -moz-appearance: textfield;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .invoice-row .allocation-amount:focus {
        outline: none;
        border-color: var(--pp-accent);
        box-shadow: 0 0 0 4px rgba(29, 118, 201, .13);
    }
    .invoice-row .allocation-amount:disabled { background: var(--pp-raise); color: var(--pp-muted); }
    .pp-alloc-foot {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid var(--pp-hair);
        font-size: 12px;
        color: var(--pp-muted);
    }
    .pp-alloc-empty {
        text-align: center;
        padding: 24px 16px;
        border: 1px dashed var(--pp-border);
        border-radius: 12px;
        background: var(--pp-raise);
        color: var(--pp-muted);
        font-size: 13px;
    }

    /* Review & submit rail --------------------------------------------- */
    .pp-review .pp-card__body { display: flex; flex-direction: column; gap: 2px; }
    .pp-review-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 9px 0;
        border-bottom: 1px solid var(--pp-hair);
        font-size: 13px;
    }
    .pp-review-row span { color: var(--pp-muted); font-weight: 600; }
    .pp-review-row b { font-weight: 800; font-variant-numeric: tabular-nums; }
    .pp-review-row.is-net { border-bottom: 0; }
    .pp-review-row.is-net span { color: var(--pp-ink); font-weight: 800; }
    .pp-review-row.is-net b { color: var(--pp-accent); font-size: 17px; }
    #paymentSubmit {
        width: 100%;
        margin-top: 14px;
        min-height: 46px;
        justify-content: center;
        border-radius: 11px;
        font-size: 14.5px;
        font-weight: 800;
        background: var(--pp-brand);
    }
    .pp-review .hint2 { margin: 8px 0 0; font-size: 11.5px; color: var(--pp-muted); text-align: center; }

    /* History --------------------------------------------------------- */
    .pp-history .pp-card__body { padding: 4px 0 0; }
    .pp-history table { width: 100%; border-collapse: collapse; border: 0; background: transparent; }
    .pp-history th {
        text-align: left;
        padding: 10px 18px;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--pp-muted);
        background: var(--pp-raise);
        border-bottom: 1px solid var(--pp-hair);
    }
    .pp-history td { padding: 12px 18px; border-bottom: 1px solid var(--pp-hair); font-size: 13px; }
    .pp-history tbody tr:last-child td { border-bottom: 0; }
    .pp-pill {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        text-transform: capitalize;
    }
    .pp-pill.credit { background: #e7f6ef; color: #0a6c4a; }
    .pp-pill.debit { background: #fdecec; color: #b42318; }

    @media (max-width: 980px) {
        .pp-grid { grid-template-columns: minmax(0, 1fr); }
        .pp-col--side { position: static; }
        .pp-col--side .pp-amount { order: -1; }
        .pp-review { position: sticky; bottom: 8px; z-index: 5; box-shadow: var(--pp-shadow-float); }
        .pp-review .pp-card__head { display: none; }
        .pp-review .pp-card__body { flex-direction: row; flex-wrap: wrap; align-items: center; gap: 8px 18px; padding: 12px 14px; }
        .pp-review-row { flex-direction: column; align-items: flex-start; gap: 1px; padding: 0; border: 0; }
        .pp-review-row span { font-size: 9.5px; text-transform: uppercase; letter-spacing: .4px; }
        .pp-review-row b { font-size: 15px; }
        .pp-review-row.is-net b { font-size: 15px; }
        #paymentSubmit { width: auto; flex: 1 1 160px; margin-top: 0; margin-left: auto; }
        .pp-review .hint2 { display: none; }
    }
    @media (max-width: 720px) {
        .pp-balance { grid-template-columns: 1fr 1fr; gap: 14px 0; padding: 16px; }
        .pp-bal, .pp-bal + .pp-bal { border: 0; padding: 2px 0; }
        .pp-bal.is-after { grid-column: 1 / -1; border-top: 1px solid var(--pp-hair); padding-top: 12px; margin-top: 2px; }
        .pp-fields { grid-template-columns: 1fr; }
    }
    @media (max-width: 520px) {
        .pp-seg { grid-template-columns: 1fr 1fr; }
        #amountInput { font-size: 34px; }
        .invoice-row { grid-template-columns: auto 1fr; row-gap: 10px; }
        .invoice-row .pp-inv-due { grid-column: 2; text-align: left; }
        .invoice-row .allocation-amount { grid-column: 1 / -1; width: 100%; }
        #paymentSubmit { width: 100%; }
    }
</style>

<div class="pp">
    <div class="pp-top">
        <div>
            <h1>Record Party Payment</h1>
            <div class="pp-tags">
                <span class="pp-tag">{{ $customer->name }}</span>
                @if ($customer->connection_id)
                    <span class="pp-tag">ID&nbsp;{{ $customer->connection_id }}</span>
                @endif
            </div>
        </div>
        <a class="pp-back" href="{{ route('customers.show', $customer) }}">&larr;&nbsp;Back to party</a>
    </div>

    <section class="pp-balance">
        <div class="pp-bal is-due">
            <span class="k">Total due</span>
            <span class="v num" id="summaryDue">{{ number_format($totalDue, 2) }}</span>
        </div>
        <div class="pp-bal">
            <span class="k">Advance balance</span>
            <span class="v num" id="summaryAdvance">{{ number_format($advanceBalance, 2) }}</span>
        </div>
        <div class="pp-bal">
            <span class="k">Net now</span>
            <span class="v num" id="summaryNet">{{ number_format($netBalance, 2) }}</span>
        </div>
        <div class="pp-bal is-after">
            <span class="k">Net after this payment</span>
            <span class="v num" id="summaryAfter">{{ number_format($netBalance, 2) }}</span>
        </div>
    </section>

    <form method="post" action="{{ route('customers.payments.store', $customer) }}" id="paymentForm">
        @csrf

        <div class="pp-grid">
            <div class="pp-col">
                <section class="pp-card">
                    <div class="pp-card__head"><h2>Method &amp; details</h2></div>
                    <div class="pp-card__body">
                        <div class="pp-fields">
                            <div class="pp-method">
                                <div class="pp-method-top">
                                    <label class="pp-seg-label">Payment method</label>
                                    @include('partials.payment_default_checkbox')
                                </div>
                                <div class="pp-seg" role="group" aria-label="Payment method">
                                    @foreach ($methodOptions as $value => $label)
                                        <button type="button" data-method="{{ $value }}" aria-pressed="{{ $selectedPaymentMethod === $value ? 'true' : 'false' }}">{{ $label }}</button>
                                    @endforeach
                                </div>
                                <select id="paymentMethod" name="payment_method" required style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none;">
                                    @foreach ($methodOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($selectedPaymentMethod === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="pp-f">
                                <label for="paymentDate">Payment date</label>
                                <input id="paymentDate" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" required>
                            </div>

                            <div class="pp-f">
                                <label for="referenceInput">Reference <span class="opt">optional</span></label>
                                <input id="referenceInput" type="text" name="reference" value="{{ old('reference') }}" placeholder="Transaction ID / receipt no">
                            </div>

                            <div class="pp-f full" id="accountSelectWrap">
                                <label for="paymentAccount">Account</label>
                                <select id="paymentAccount" name="payment_account_id">
                                    <option value="">Select account</option>
                                </select>
                            </div>

                            <div class="pp-f full">
                                <label for="noteInput">Note <span class="opt">optional</span></label>
                                <input id="noteInput" type="text" name="note" value="{{ old('note') }}" placeholder="Short note">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="pp-card">
                    <div class="pp-card__head">
                        <h2>Apply to invoices</h2>
                        @if ($dueInvoices->isNotEmpty())
                            <span class="hint">{{ $dueInvoices->count() }} outstanding &bull; {{ number_format($totalDue, 2) }} due</span>
                        @endif
                    </div>
                    <div class="pp-card__body">
                        @if ($dueInvoices->isNotEmpty())
                            <div class="pp-alloc-head">
                                <strong>Tick what this payment covers</strong>
                                <label class="pp-selectall">
                                    <input id="toggleAllInvoices" type="checkbox" checked>
                                    <span>Select all</span>
                                </label>
                            </div>
                            <div class="pp-inv-list">
                                @foreach ($dueInvoices as $invoice)
                                    @php
                                        $defaultAllocation = (float) old('invoice_allocations.'.$invoice->id, 0);
                                    @endphp
                                    <div class="invoice-row" data-id="{{ $invoice->id }}" data-due="{{ $invoice->due_amount }}">
                                        <input
                                            class="invoice-toggle"
                                            type="checkbox"
                                            value="1"
                                            checked
                                            data-id="{{ $invoice->id }}"
                                            aria-label="Use {{ $invoice->invoice_no }}"
                                        >
                                        <div>
                                            <div class="pp-inv-name">
                                                @if ($canOpenInvoices)
                                                    <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                                                @else
                                                    {{ $invoice->invoice_no }}
                                                @endif
                                            </div>
                                            <div class="pp-inv-meta">{{ $invoice->formatted_billing_month }} &bull; {{ $invoice->due_date?->format('d/m/Y') ?? 'No due date' }}</div>
                                        </div>
                                        <span class="pp-inv-due">due<b class="num">{{ number_format($invoice->due_amount, 2) }}</b></span>
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
                                    </div>
                                @endforeach
                            </div>
                            <div class="pp-alloc-foot" id="previewText">Enter amount to preview invoice and balance impact.</div>
                        @else
                            <p class="pp-alloc-empty">No outstanding invoices. The full amount will be saved as advance balance.</p>
                            <span id="previewText" style="display:none;">Enter amount to preview the balance impact.</span>
                        @endif
                    </div>
                </section>
            </div>

            <div class="pp-col pp-col--side">
                <section class="pp-card pp-review">
                    <div class="pp-card__head"><h2>Review &amp; submit</h2></div>
                    <div class="pp-card__body">
                        <div class="pp-review-row">
                            <span>Due after</span>
                            <b class="num" id="previewDue">0.00</b>
                        </div>
                        <div class="pp-review-row">
                            <span>Advance after</span>
                            <b class="num" id="previewAdvance">0.00</b>
                        </div>
                        <div class="pp-review-row is-net">
                            <span>Net after</span>
                            <b class="num" id="previewNet">0.00</b>
                        </div>
                        <button id="paymentSubmit" class="btn" type="submit">Submit Payment</button>
                        <p class="hint2">The party is not charged &mdash; you are recording money received.</p>
                    </div>
                </section>

                <section class="pp-amount">
                    <div class="lead">Payment amount</div>
                    <div class="pp-amount-field">
                        <span class="cur">&#2547;</span>
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
                    <p class="sub">Applied to the ticked invoices first; the remainder is stored as advance.</p>

                    <label class="pp-switch">
                        <input type="checkbox" id="keepAsAdvance" name="keep_as_advance" value="1" @checked(old('keep_as_advance') === '1')>
                        <span class="track"></span>
                        <span class="txt">
                            <strong>Keep the whole amount as advance</strong>
                            <span>No invoice is reduced &mdash; the money sits on the party balance.</span>
                        </span>
                    </label>
                </section>
            </div>

            <section class="pp-card pp-history">
                <div class="pp-card__head">
                    <h2>Advance balance history</h2>
                    <span class="hint">last {{ $balanceTransactions->count() }}</span>
                </div>
                <div class="pp-card__body">
                    <div style="overflow-x:auto;">
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
                                        <td class="num">{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                                        <td><span class="pp-pill {{ $transaction->direction === 'credit' ? 'credit' : 'debit' }}">{{ $transaction->direction }}</span></td>
                                        <td class="num">{{ number_format($transaction->amount, 2) }}</td>
                                        <td class="num">{{ number_format($transaction->balance_after, 2) }}</td>
                                        <td>{{ $transaction->reference ?? '—' }}</td>
                                        <td>{{ $transaction->note ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="color:var(--pp-muted);">No advance balance history yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </form>
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

<script>
(function () {
    var select = document.getElementById('paymentMethod');
    var seg = document.querySelector('.pp-seg');
    if (!select || !seg) return;
    var buttons = [].slice.call(seg.querySelectorAll('button[data-method]'));
    function paint() {
        buttons.forEach(function (b) {
            b.setAttribute('aria-pressed', b.dataset.method === select.value ? 'true' : 'false');
        });
    }
    buttons.forEach(function (b) {
        b.addEventListener('click', function () {
            if (select.value === b.dataset.method) return;
            select.value = b.dataset.method;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            paint();
        });
    });
    paint();
})();
</script>
@endsection
