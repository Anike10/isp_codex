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
        --line: #dde4ef;
        --soft: #eef3fa;
        --ink: #0f172a;
        --muted: #64748b;
        --primary: #1d76c9;
        --good: #0d9488;
        --warn: #c2410c;
        display: flex;
        flex-direction: column;
        gap: 14px;
        color: var(--ink);
    }
    .payment-page .page-help {
        display: none;
    }
    .payment-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }
    .payment-topbar h1 {
        margin: 0;
        font-size: 28px;
        letter-spacing: -0.01em;
    }
    .customer-subtitle {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 13px;
    }
    .topbar-actions {
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .help-dot {
        width: 28px;
        height: 28px;
        border: 1px solid var(--line);
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: #334155;
        font-weight: 700;
        font-size: 14px;
        cursor: help;
        text-decoration: none;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }
    .summary-card {
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        padding: 12px;
    }
    .summary-card .label {
        color: var(--muted);
        font-size: 12px;
        margin-bottom: 6px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .summary-card .value {
        font-size: 20px;
        line-height: 1.2;
        font-weight: 700;
    }
    .form-wrap {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 14px;
        align-items: start;
    }
    .panel {
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        padding: 14px;
    }
    .panel h2 {
        margin: 0 0 10px;
        font-size: 18px;
    }
    .field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
    }
    .field.full {
        grid-column: 1 / -1;
    }
    .field label {
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .field input,
    .field select,
    .field textarea {
        width: 100%;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #f8fbff;
        padding: 10px 12px;
        font: inherit;
        color: var(--ink);
    }
    .field input:focus,
    .field select:focus,
    .field textarea:focus {
        outline: 2px solid rgba(29, 118, 201, 0.2);
        border-color: rgba(29, 118, 201, 0.8);
        background: #fff;
    }
    .muted {
        color: var(--muted);
        font-size: 12px;
    }
    .preview-block {
        border: 1px dashed #c7d2e3;
        border-radius: 10px;
        padding: 10px 12px;
        background: #f7faff;
        font-size: 13px;
        line-height: 1.45;
    }
    .preview-block .muted {
        margin-bottom: 4px;
    }
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-top: 6px;
    }
    .preview-cell {
        border: 1px solid #d8e2f0;
        border-radius: 8px;
        padding: 6px 8px;
        background: #fff;
    }
    .preview-cell .preview-label {
        color: var(--muted);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .preview-cell .preview-value {
        font-size: 14px;
        font-weight: 700;
    }
    .keep-advance {
        border: 1px solid #dbe4f2;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fbfdff;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .keep-advance input {
        margin-top: 3px;
        accent-color: var(--primary);
    }
    .allocation-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 13px;
    }
    .allocation-toolbar .actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--muted);
    }
    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 520px;
    }
    .invoice-table th,
    .invoice-table td {
        border-bottom: 1px solid #e6edf7;
        padding: 8px 6px;
        text-align: left;
        vertical-align: middle;
    }
    .invoice-table th {
        color: var(--muted);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .invoice-row.disabled .invoice-toggle,
    .invoice-row.disabled .allocation-amount {
        opacity: .65;
    }
    .invoice-toggle {
        width: 16px;
        height: 16px;
        accent-color: var(--primary);
    }
    .allocation-amount {
        width: 120px;
        text-align: right;
    }
    .allocation-amount[readonly] {
        background: #f8fafc;
    }
    .allocation-empty {
        color: var(--muted);
        font-size: 13px;
        padding: 10px 0;
    }
    .btn-row {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 12px;
    }
    .btn-row .btn {
        min-width: 148px;
        justify-content: center;
    }
    .history-panel {
        margin-top: 2px;
    }
    @media (max-width: 1100px) {
        .form-wrap {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 860px) {
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .field-grid {
            grid-template-columns: 1fr;
        }
        .preview-grid {
            grid-template-columns: 1fr;
        }
        .invoice-table {
            min-width: 0;
        }
    }
    @media (max-width: 560px) {
        .payment-topbar {
            align-items: stretch;
            flex-direction: column;
        }
        .topbar-actions {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<div class="payment-page">
    <div class="payment-topbar">
        <div>
            <h1>Record Party Payment</h1>
            <p class="customer-subtitle">{{ $customer->name }} • {{ $customer->connection_id }}</p>
        </div>
        <div class="topbar-actions">
            <a class="help-dot" href="javascript:void(0);" title="Keep amount entry simple: choose payment method/account, type amount, tick invoices to adjust. Uncheck invoices to skip.">?</a>
            <a class="btn light" href="{{ route('customers.show', $customer) }}">Back</a>
        </div>
    </div>

    <section class="summary-grid">
        <article class="summary-card">
            <div class="label">Running Total Due</div>
            <div class="value" id="summaryDue">{{ number_format($totalDue, 2) }}</div>
        </article>
        <article class="summary-card">
            <div class="label">Advance Balance</div>
            <div class="value" id="summaryAdvance">{{ number_format($advanceBalance, 2) }}</div>
        </article>
        <article class="summary-card">
            <div class="label">Net Balance</div>
            <div class="value" id="summaryNet">{{ number_format($netBalance, 2) }}</div>
        </article>
        <article class="summary-card">
            <div class="label">After Payment Preview</div>
            <div class="value" id="summaryAfter">{{ number_format($netBalance, 2) }}</div>
        </article>
    </section>

    <form method="post" action="{{ route('customers.payments.store', $customer) }}" class="card" id="paymentForm">
        @csrf

        <div class="form-wrap">
            <section class="panel">
                <h2>
                    Payment Details
                    <small class="muted" style="font-size:12px; margin-left:6px; font-weight:500;">(Input + allocation are fast)</small>
                </h2>

                <label class="keep-advance">
                    <input type="checkbox" id="keepAsAdvance" name="keep_as_advance" value="1" @checked(old('keep_as_advance') === '1')>
                    <span>
                        <strong>Keep this payment as advance</strong>
                        <span class="muted">Enable when this money should not reduce any invoice automatically.</span>
                    </span>
                </label>

                <div class="field-grid" style="margin-top:12px">
                    <div class="field">
                        <label for="amountInput">
                            Amount
                            <span title="This value is used for invoice allocations first, remaining stays as advance.">ⓘ</span>
                        </label>
                        <input
                            id="amountInput"
                            name="amount"
                            type="text" inputmode="decimal"
                            autocomplete="off"
                            value="{{ $amountDefault }}"
                            required
                        >
                        <p class="muted" style="margin:0;">If payment is for multiple invoices, type total amount once.</p>
                        <p class="muted" style="margin:0;">This field has no mouse-wheel or arrow-step adjustment.</p>
                    </div>

                    <div class="field">
                        <label for="paymentDate">Payment Date</label>
                        <input id="paymentDate" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" required>
                    </div>

                    <div class="field">
                        <label for="paymentMethod">Payment Method</label>
                        <div class="field" style="margin:0; gap:6px;">
                            <select id="paymentMethod" name="payment_method" required>
                                <option value="cash" @selected($selectedPaymentMethod === 'cash')>Cash</option>
                                <option value="bkash" @selected($selectedPaymentMethod === 'bkash')>bKash</option>
                                <option value="nagad" @selected($selectedPaymentMethod === 'nagad')>Nagad</option>
                                <option value="bank" @selected($selectedPaymentMethod === 'bank')>Bank</option>
                            </select>
                            <div class="field" style="margin:0; gap:2px;">
                                @include('partials.payment_default_checkbox')
                            </div>
                        </div>
                    </div>

                    <div class="field" id="accountSelectWrap">
                        <label for="paymentAccount">Account</label>
                        <select id="paymentAccount" name="payment_account_id">
                            <option value="">Select account</option>
                        </select>
                    </div>

                    <div class="field full">
                        <label for="referenceInput">Reference</label>
                        <input id="referenceInput" type="text" name="reference" value="{{ old('reference') }}" placeholder="Transaction ID / receipt no">
                    </div>

                    <div class="field full">
                        <label for="noteInput">Note</label>
                        <textarea id="noteInput" name="note" rows="2" placeholder="Optional note">{{ old('note') }}</textarea>
                    </div>
                </div>

                <div class="preview-block" style="margin-top:12px">
                    <div class="muted">After Payment Preview</div>
                    <div class="preview-grid">
                        <div class="preview-cell">
                            <div class="preview-label">Due Remaining</div>
                            <div class="preview-value" id="previewDue">0.00</div>
                        </div>
                        <div class="preview-cell">
                            <div class="preview-label">Advance After</div>
                            <div class="preview-value" id="previewAdvance">0.00</div>
                        </div>
                        <div class="preview-cell">
                            <div class="preview-label">Net Balance</div>
                            <div class="preview-value" id="previewNet">0.00</div>
                        </div>
                    </div>
                    <div class="muted" id="previewText" style="margin-top:6px">Enter amount to preview invoice and balance impact.</div>
                </div>
            </section>

            <section class="panel">
                <h2>
                    Invoice Allocation
                    <a
                        class="help-dot"
                        href="javascript:void(0);"
                        title="Check the invoices you want to pay from this entry. If unchecked, that invoice won't be reduced."
                        style="margin-left: 6px;"
                    >?</a>
                </h2>

                @if ($dueInvoices->isNotEmpty())
                    <div class="allocation-toolbar">
                        <strong style="font-size: 13px;">Outstanding Invoices ({{ $dueInvoices->count() }})</strong>
                        <label class="actions">
                            <input id="toggleAllInvoices" type="checkbox" checked>
                            <span>Select all</span>
                        </label>
                    </div>
                    <div class="table-scroll" style="overflow:auto;">
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th style="width:46px;">Use</th>
                                    <th>Invoice</th>
                                    <th>Due Amount</th>
                                    <th style="width:170px;">Apply From This Payment</th>
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
                                            @if ($canOpenInvoices)
                                                <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                                            @else
                                                {{ $invoice->invoice_no }}
                                            @endif
                                            <div class="muted">{{ $invoice->formatted_billing_month }} • {{ $invoice->due_date?->format('d/m/Y') ?? 'No due date' }}</div>
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
                    <p class="allocation-empty">No outstanding invoices found. Full amount will be saved as advance.</p>
                @endif
            </section>
        </div>

        <div class="btn-row">
            <button id="paymentSubmit" class="btn" type="submit">Submit Payment</button>
        </div>
    </form>

    <section class="history-panel card">
        <h2>Advance Balance History</h2>
        <div class="table-wrap" style="overflow:auto;">
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
