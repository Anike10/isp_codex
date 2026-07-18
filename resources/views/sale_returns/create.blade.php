@extends('layouts.app')

@section('content')
<style>
    .serial-picker {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .serial-picker button {
        border: 1px solid var(--line);
        border-radius: 6px;
        background: #fff;
        color: var(--ink);
        padding: 6px 9px;
        cursor: pointer;
        font: inherit;
        font-size: 13px;
        min-height: 32px;
    }

    .serial-picker button.is-selected {
        border-color: var(--brand);
        background: #e7f7ef;
        color: #05603a;
        font-weight: 700;
    }
</style>

<div class="topbar">
    <div><h1>New Sale Return</h1><div class="muted">Select a sold invoice, enter returned quantities, and restore stock</div></div>
    <a class="btn light" href="{{ route('sale-returns.index') }}">Back</a>
</div>

@if (! $invoice)
    <form method="get" class="card filter-form">
        <div class="full">
            <label>Invoice</label>
            <select name="invoice_id" required>
                <option value="">Select invoice</option>
                @foreach ($invoices as $candidate)
                    <option value="{{ $candidate->id }}">{{ $candidate->invoice_no }} - {{ $candidate->customer->name }} - {{ number_format($candidate->total, 2) }}</option>
                @endforeach
            </select>
        </div>
        <div class="full"><button class="btn" type="submit">Continue</button></div>
    </form>
@else
    <section class="card" style="margin-bottom:16px">
        <h2>Invoice</h2>
        <p><strong>{{ $invoice->invoice_no }}</strong> - {{ $invoice->customer->name }} - Total {{ number_format($invoice->total, 2) }}</p>
        <p class="muted">Return credit will be added to this party's advance balance.</p>
    </section>

    <form method="post" action="{{ route('sale-returns.store') }}" class="card form-grid" onsubmit="return confirm('Save this sale return and restore stock?');">
        @csrf
        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

        <div>
            <label>Return No</label>
            <input name="return_no" value="{{ old('return_no', $returnNo) }}" required>
        </div>
        <div>
            <label>Return Date</label>
            <input type="date" name="return_date" value="{{ old('return_date', now()->toDateString()) }}" required>
        </div>
        <div class="full">
            <label>Note</label>
            <textarea name="note" placeholder="Return reason, refund note, or operator note">{{ old('note') }}</textarea>
        </div>

        <div class="full">
            <h2>Items</h2>
            <table>
                <thead><tr><th>Product</th><th>Sold</th><th>Remaining</th><th>Return Qty</th><th>Serials</th><th>Serial-less Qty</th><th>Unit</th></tr></thead>
                <tbody>
                @forelse ($returnable as $index => $row)
                    @php($item = $row['item'])
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            <input type="hidden" name="items[{{ $index }}][invoice_item_id]" value="{{ $item->id }}">
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $row['remaining_quantity'] }}</td>
                        <td>
                            @if ($item->product?->track_serial_numbers)
                                <input value="Auto from serials + serial-less" readonly>
                            @else
                                <input type="number" name="items[{{ $index }}][quantity]" min="0" max="{{ $row['remaining_quantity'] }}" value="{{ old("items.$index.quantity", 0) }}">
                            @endif
                        </td>
                        <td>
                            @if ($item->product?->track_serial_numbers && $row['available_serials'] !== [])
                                <textarea class="serial-input" name="items[{{ $index }}][serial_numbers]" placeholder="One or more sold serials">{{ old("items.$index.serial_numbers") }}</textarea>
                                <div class="serial-picker" data-target="items[{{ $index }}][serial_numbers]">
                                    @foreach ($row['available_serials'] as $serial)
                                        <button type="button" data-serial="{{ $serial }}">{{ $serial }}</button>
                                    @endforeach
                                </div>
                                <div class="muted">Available: {{ implode(', ', $row['available_serials']) }}</div>
                            @else
                                <span class="muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if ($item->product?->track_serial_numbers)
                                <input type="number" name="items[{{ $index }}][serialless_quantity]" min="0" max="{{ $row['remaining_serialless_quantity'] }}" value="{{ old("items.$index.serialless_quantity", 0) }}">
                                <span class="muted">Max {{ $row['remaining_serialless_quantity'] }}</span>
                            @else
                                <span class="muted">N/A</span>
                            @endif
                        </td>
                        <td>{{ number_format($item->unit_price, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Nothing remains returnable on this invoice.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="full actions">
            <button class="btn" type="submit" @disabled($returnable->isEmpty())>Save Sale Return</button>
            <a class="btn light" href="{{ route('invoices.show', $invoice) }}">Cancel</a>
        </div>
    </form>
@endif
<script>
function parseSerialInput(value) {
    return value.split(/[\n,]+/).map(item => item.trim()).filter(Boolean);
}

function syncSerialPicker(picker) {
    const input = document.querySelector(`[name="${CSS.escape(picker.dataset.target)}"]`);
    if (! input) {
        return;
    }

    const selected = new Set(parseSerialInput(input.value));
    picker.querySelectorAll('button[data-serial]').forEach(button => {
        button.classList.toggle('is-selected', selected.has(button.dataset.serial));
    });
}

document.querySelectorAll('.serial-picker').forEach(picker => {
    syncSerialPicker(picker);

    picker.addEventListener('click', event => {
        const button = event.target.closest('button[data-serial]');
        if (! button) {
            return;
        }

        const input = document.querySelector(`[name="${CSS.escape(picker.dataset.target)}"]`);
        if (! input) {
            return;
        }

        const serials = parseSerialInput(input.value);
        const existingIndex = serials.indexOf(button.dataset.serial);

        if (existingIndex >= 0) {
            serials.splice(existingIndex, 1);
        } else {
            serials.push(button.dataset.serial);
        }

        input.value = serials.join(', ');
        syncSerialPicker(picker);
    });
});

document.querySelectorAll('.serial-input').forEach(input => {
    input.addEventListener('input', () => {
        document.querySelectorAll(`.serial-picker[data-target="${CSS.escape(input.name)}"]`).forEach(syncSerialPicker);
    });
});
</script>
@endsection
