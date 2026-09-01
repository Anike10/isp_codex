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
        request()->filled('amount')
            ? number_format((float) request('amount'), 2, '.', '')
            : ($totalDue > 0 ? number_format($totalDue, 2, '.', '') : '')
    );
    $methodOptions = ['cash' => 'Cash', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank'];
@endphp

<style>
    .pp {
        --pp-ink: #0b1220;
        --pp-ink-soft: #334155;
        --pp-muted: #64748b;
        --pp-border: #e6e9f0;
        --pp-hair: #eef1f6;
        --pp-raise: #f8fafc;
        --pp-surface: #ffffff;
        --pp-accent: #2563eb;
        --pp-accent-ink: #1d4ed8;
        --pp-accent-soft: #eff5ff;
        --pp-owed: #dc2626;
        --pp-ring: 0 0 0 4px rgba(37, 99, 235, .14);
        --pp-shadow-sm: 0 1px 2px rgba(15, 23, 42, .05);
        --pp-shadow-md: 0 1px 3px rgba(15, 23, 42, .06), 0 14px 32px -18px rgba(15, 23, 42, .22);
        --pp-shadow-bar: 0 -1px 0 rgba(15, 23, 42, .04), 0 -14px 34px -18px rgba(15, 23, 42, .22);

        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: var(--pp-ink);
        display: flex;
        flex-direction: column;
        gap: 18px;
        font-size: 14px;
        line-height: 1.5;
        max-width: 860px;
        margin: 0 auto;
        padding-bottom: 96px;
    }
    .pp *, .pp *::before, .pp *::after { box-sizing: border-box; }
    .pp .num { font-variant-numeric: tabular-nums; }

    /* Header ---------------------------------------------------------- */
    .pp-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px 20px;
        flex-wrap: wrap;
    }
    .pp-top h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; }
    .pp-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .pp-tag {
        display: inline-flex; align-items: center; gap: 4px;
        background: var(--pp-raise); border: 1px solid var(--pp-border);
        color: var(--pp-ink-soft); font-size: 12px; font-weight: 600;
        padding: 3px 9px; border-radius: 999px;
    }
    .pp-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 13px; font-weight: 600; text-decoration: none;
        color: var(--pp-ink-soft); background: var(--pp-surface);
        border: 1px solid var(--pp-border); border-radius: 10px;
        padding: 7px 12px; white-space: nowrap;
    }
    .pp-back:hover { color: var(--pp-accent-ink); border-color: var(--pp-accent); }

    /* Errors -------------------------------------------------------------- */
    .pp-errors {
        background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
        border-radius: 12px; padding: 12px 14px; font-size: 13px;
    }
    .pp-errors ul { margin: 6px 0 0; padding-left: 18px; }
    .pp-field-error { display: block; margin-top: 5px; font-size: 12px; font-weight: 600; color: var(--pp-owed); }

    /* Balance strip ---------------------------------------------------- */
    .pp-balance {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        background: var(--pp-surface); border: 1px solid var(--pp-border);
        border-radius: 16px; box-shadow: var(--pp-shadow-sm); overflow: hidden;
    }
    .pp-bal { padding: 14px 16px; display: flex; flex-direction: column; gap: 4px; }
    .pp-bal + .pp-bal { border-left: 1px solid var(--pp-hair); }
    .pp-bal .k { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--pp-muted); }
    .pp-bal .v { font-size: 19px; font-weight: 800; letter-spacing: -0.01em; }
    .pp-bal.is-due .v { color: var(--pp-owed); }
    .pp-bal.is-after .k { color: var(--pp-accent-ink); }
    .pp-bal.is-after .v { color: var(--pp-accent-ink); }

    /* Cards ---------------------------------------------------------------- */
    .pp-stack { display: flex; flex-direction: column; gap: 18px; }
    .pp-card {
        background: var(--pp-surface); border: 1px solid var(--pp-border);
        border-radius: 16px; box-shadow: var(--pp-shadow-sm);
    }
    .pp-card__head {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 14px 18px; border-bottom: 1px solid var(--pp-hair);
    }
    .pp-card__head h2 { margin: 0; font-size: 14px; font-weight: 800; letter-spacing: -0.01em; }
    .pp-card__head .hint { font-size: 12px; color: var(--pp-muted); font-weight: 600; }
    .pp-card__body { padding: 18px; }

    /* Hero: amount ------------------------------------------------------- */
    .pp-amount { border: 1px solid var(--pp-accent); box-shadow: var(--pp-shadow-md); }
    .pp-amount .pp-card__head { background: var(--pp-accent-soft); border-bottom-color: #dbe7ff; }
    .pp-amount .pp-card__head h2 { color: var(--pp-accent-ink); }
    .pp-amount-field {
        display: flex; align-items: center; gap: 8px;
        border: 1.5px solid var(--pp-border); border-radius: 14px;
        background: var(--pp-surface); padding: 10px 16px;
        transition: border-color .12s ease, box-shadow .12s ease;
    }
    .pp-amount-field:focus-within { border-color: var(--pp-accent); box-shadow: var(--pp-ring); }
    .pp-amount-field .cur { font-size: 30px; font-weight: 700; color: var(--pp-muted); line-height: 1; }
    #amountInput {
        flex: 1; min-width: 0; border: 0; background: transparent; outline: none;
        font-size: 40px; font-weight: 800; letter-spacing: -0.02em; color: var(--pp-ink);
        font-variant-numeric: tabular-nums; padding: 4px 0;
    }
    #amountInput::placeholder { color: #cbd5e1; }
    .pp-amount .sub { margin: 10px 2px 0; font-size: 12px; color: var(--pp-muted); }

    .pp-quick { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .pp-quick button {
        border: 1px solid var(--pp-border); background: var(--pp-raise);
        color: var(--pp-ink-soft); font-size: 12px; font-weight: 700;
        padding: 6px 11px; border-radius: 999px; cursor: pointer;
    }
    .pp-quick button:hover { border-color: var(--pp-accent); color: var(--pp-accent-ink); background: var(--pp-accent-soft); }

    /* Switch ------------------------------------------------------------- */
    .pp-switch { display: flex; gap: 12px; align-items: flex-start; margin-top: 16px; cursor: pointer; }
    .pp-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
    .pp-switch .track {
        flex: 0 0 auto; width: 40px; height: 24px; border-radius: 999px;
        background: #cbd5e1; position: relative; transition: background .15s ease; margin-top: 1px;
    }
    .pp-switch .track::after {
        content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px;
        border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.25); transition: transform .15s ease;
    }
    .pp-switch input:checked + .track { background: var(--pp-accent); }
    .pp-switch input:checked + .track::after { transform: translateX(16px); }
    .pp-switch .txt strong { display: block; font-size: 13px; font-weight: 700; }
    .pp-switch .txt span { display: block; font-size: 12px; color: var(--pp-muted); }

    /* Method & fields -------------------------------------------------- */
    .pp-method-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
    .pp-seg-label { font-size: 12px; font-weight: 700; color: var(--pp-ink-soft); }
    .pp-seg {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;
        background: var(--pp-raise); border: 1px solid var(--pp-border); border-radius: 12px; padding: 5px;
    }
    .pp-seg button {
        border: 0; background: transparent; border-radius: 8px; padding: 8px 6px;
        font-size: 13px; font-weight: 700; color: var(--pp-ink-soft); cursor: pointer; transition: all .12s ease;
    }
    .pp-seg button[aria-pressed="true"] { background: var(--pp-surface); color: var(--pp-accent-ink); box-shadow: var(--pp-shadow-sm); }

    .pp-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 16px; }
    .pp-f { display: flex; flex-direction: column; gap: 6px; }
    .pp-f.full { grid-column: 1 / -1; }
    .pp-f label { font-size: 12px; font-weight: 700; color: var(--pp-ink-soft); }
    .pp-f label .opt { font-weight: 500; color: var(--pp-muted); }
    .pp-f input, .pp-f select {
        width: 100%; border: 1.5px solid var(--pp-border); border-radius: 10px;
        padding: 9px 11px; font-size: 14px; color: var(--pp-ink); background: var(--pp-surface);
        outline: none; transition: border-color .12s ease, box-shadow .12s ease;
    }
    .pp-f input:focus, .pp-f select:focus { border-color: var(--pp-accent); box-shadow: var(--pp-ring); }

    /* Invoice allocation --------------------------------------------------- */
    .pp-alloc-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
    .pp-alloc-head strong { font-size: 13px; }
    .pp-selectall { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--pp-ink-soft); cursor: pointer; }
    .pp-selectall input { width: 15px; height: 15px; accent-color: var(--pp-accent); }
    .pp-inv-list { display: flex; flex-direction: column; gap: 8px; }
    .invoice-row {
        display: grid; grid-template-columns: auto 1fr auto 140px; align-items: center; gap: 10px 12px;
        border: 1px solid var(--pp-border); border-radius: 12px; padding: 10px 12px; background: var(--pp-surface);
    }
    .invoice-row.disabled { opacity: .5; }
    .invoice-row .invoice-toggle { width: 16px; height: 16px; accent-color: var(--pp-accent); }
    .pp-inv-name { font-size: 13px; font-weight: 700; }
    .pp-inv-name a { color: var(--pp-accent-ink); text-decoration: none; }
    .pp-inv-name a:hover { text-decoration: underline; }
    .pp-inv-meta { font-size: 11.5px; color: var(--pp-muted); }
    .pp-inv-due { font-size: 12px; color: var(--pp-muted); text-align: right; white-space: nowrap; }
    .pp-inv-due b { display: block; font-size: 14px; color: var(--pp-owed); }
    .invoice-row .allocation-amount {
        width: 100%; border: 1.5px solid var(--pp-border); border-radius: 9px; padding: 7px 9px;
        font-size: 13px; text-align: right; font-variant-numeric: tabular-nums; outline: none;
    }
    .invoice-row .allocation-amount:focus { border-color: var(--pp-accent); box-shadow: var(--pp-ring); }
    .invoice-row .allocation-amount:disabled { background: var(--pp-raise); color: var(--pp-muted); }
    .pp-alloc-foot { margin-top: 12px; font-size: 12px; color: var(--pp-muted); }
    .pp-alloc-empty { margin: 0; font-size: 13px; color: var(--pp-muted); }

    /* History table --------------------------------------------------------- */
    .pp-history table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .pp-history th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--pp-muted); padding: 8px 10px; border-bottom: 1px solid var(--pp-hair); }
    .pp-history td { padding: 9px 10px; border-bottom: 1px solid var(--pp-hair); }
    .pp-history tr:last-child td { border-bottom: 0; }
    .pp-pill { display: inline-block; font-size: 11px; font-weight: 700; text-transform: capitalize; padding: 2px 8px; border-radius: 999px; }
    .pp-pill.credit { background: #ecfdf5; color: #047857; }
    .pp-pill.debit { background: #fef2f2; color: #b91c1c; }

    /* Sticky action bar ---------------------------------------------------- */
    .pp-bar {
        position: sticky; bottom: 0; z-index: 20;
        margin: 4px -20px 0; padding: 12px 20px;
        background: rgba(255, 255, 255, .92); backdrop-filter: blur(8px);
        border-top: 1px solid var(--pp-border); box-shadow: var(--pp-shadow-bar);
        display: flex; align-items: center; gap: 10px 18px; flex-wrap: wrap;
    }
    .pp-bar__stats { display: flex; gap: 18px; flex: 1; min-width: 200px; }
    .pp-review-row { display: flex; flex-direction: column; gap: 1px; }
    .pp-review-row span { font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--pp-muted); }
    .pp-review-row b { font-size: 16px; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pp-review-row.is-net b { color: var(--pp-accent-ink); }
    #paymentSubmit {
        border: 0; border-radius: 11px; background: var(--pp-accent); color: #fff;
        font-size: 14px; font-weight: 800; padding: 11px 22px; cursor: pointer;
        box-shadow: 0 8px 20px -8px rgba(37, 99, 235, .6);
    }
    #paymentSubmit:hover { background: var(--pp-accent-ink); }
    .pp-bar .hint2 { flex-basis: 100%; margin: 0; font-size: 11px; color: var(--pp-muted); }

    /* Responsive --------------------------------------------------------- */
    @media (max-width: 720px) {
        .pp-balance { grid-template-columns: 1fr 1fr; }
        .pp-bal { border-top: 1px solid var(--pp-hair); }
        .pp-bal:nth-child(-n+2) { border-top: 0; }
        .pp-bal:nth-child(odd) { border-left: 0; }
        .pp-fields { grid-template-columns: 1fr; }
        .invoice-row { grid-template-columns: auto 1fr; row-gap: 8px; }
        .invoice-row .pp-inv-due { grid-column: 2; text-align: left; }
        .invoice-row .allocation-amount { grid-column: 1 / -1; }
        #amountInput { font-size: 32px; }
        .pp-bar__stats { gap: 12px; }
        #paymentSubmit { flex: 1 1 100%; }
    }
    @media (max-width: 520px) {
        .pp-seg { grid-template-columns: 1fr 1fr; }
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

    @if ($errors->any())
        <div class="pp-errors">
            <strong>Please fix the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
            <span class="k">Net after payment</span>
            <span class="v num" id="summaryAfter">{{ number_format($netBalance, 2) }}</span>
        </div>
    </section>

    <form method="post" action="{{ route('customers.payments.store', $customer) }}" id="paymentForm">
        @csrf

        <div class="pp-stack">
            {{-- HERO: amount ------------------------------------------------ --}}
            <section class="pp-card pp-amount">
                <div class="pp-card__head"><h2>Payment amount</h2></div>
                <div class="pp-card__body">
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
                    @error('amount') <span class="pp-field-error">{{ $message }}</span> @enderror
                    <p class="sub">Applied to the ticked invoices first; the remainder is stored as advance. This field has no mouse-wheel or arrow-step adjustment.</p>

                    @if ($totalDue > 0)
                        <div class="pp-quick">
                            <button type="button" data-amount="{{ number_format($totalDue, 2, '.', '') }}">Full due &mdash; {{ number_format($totalDue, 2) }}</button>
                        </div>
                    @endif

                    <label class="pp-switch">
                        <input type="checkbox" id="keepAsAdvance" name="keep_as_advance" value="1" @checked(old('keep_as_advance') === '1')>
                        <span class="track"></span>
                        <span class="txt">
                            <strong>Keep the whole amount as advance</strong>
                            <span>No invoice is reduced &mdash; the money sits on the party balance.</span>
                        </span>
                    </label>
                </div>
            </section>

            {{-- Method & details ------------------------------------------- --}}
            <section class="pp-card">
                <div class="pp-card__head"><h2>Method &amp; details</h2></div>
                <div class="pp-card__body">
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

                    <div class="pp-fields">
                        <div class="pp-f">
                            <label for="paymentDate">Payment date</label>
                            <input id="paymentDate" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" required>
                            @error('payment_date') <span class="pp-field-error">{{ $message }}</span> @enderror
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
                            @error('payment_account_id') <span class="pp-field-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="pp-f full">
                            <label for="noteInput">Note <span class="opt">optional</span></label>
                            <input id="noteInput" type="text" name="note" value="{{ old('note') }}" placeholder="Short note">
                        </div>
                    </div>
                </div>
            </section>

            {{-- Apply to invoices ---------------------------------------- --}}
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
                        <div class="pp-alloc-foot" id="previewText" style="display:none;">Enter amount to preview the balance impact.</div>
                    @endif
                </div>
            </section>

            {{-- Advance balance history ---------------------------------- --}}
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

        {{-- Sticky action bar -------------------------------------------- --}}
        <div class="pp-bar">
            <div class="pp-bar__stats">
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
            </div>
            <button id="paymentSubmit" class="btn" type="submit">Submit Payment</button>
            <p class="hint2">The party is not charged &mdash; you are recording money received.</p>
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

    if (previewText) {
        if (amount <= 0) {
            previewText.textContent = 'Enter amount to preview invoice and balance impact.';
        } else if (keepAsAdvance.checked) {
            previewText.textContent = `Keep as advance mode: total payment ${money(amount)} will be added to advance now.`;
        } else {
            previewText.textContent = `Allocated ${money(allocatedTotal)} to selected invoices; remaining ${money(advanceAfter - currentAdvance)} will be kept as advance.`;
        }
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

document.querySelectorAll('.pp-quick button[data-amount]').forEach((button) => {
    button.addEventListener('click', () => {
        amountInput.value = button.dataset.amount;
        manualAllocation = false;
        amountInput.dispatchEvent(new Event('input', { bubbles: true }));
        amountInput.focus();
    });
});

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
