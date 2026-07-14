@extends('layouts.app')

@section('content')
@php
    $returnedSerials = $assignment->returns->flatMap(fn ($return) => app(\App\Support\SerialNumberParser::class)->parse((string) $return->serial_numbers))->unique()->values();
    $assignedSerials = collect(app(\App\Support\SerialNumberParser::class)->parse((string) $assignment->serial_numbers));
    $outstandingSerials = $assignedSerials->diff($returnedSerials)->values();
    $returnedSerialless = (int) $assignment->returns->sum('serialless_quantity');
    $outstandingSerialless = max(0, $assignment->serialless_quantity - $returnedSerialless);
@endphp
<div class="topbar">
    <div><h1>Employee Asset #{{ $assignment->id }}</h1><div class="muted">Issue details, outstanding quantity, and return history</div></div>
    <div class="actions"><a class="btn" href="{{ route('in-house-use.index', ['employee_id' => $assignment->employee_id]) }}">New Issue</a><a class="btn light" href="{{ route('in-house-use.report.history', ['employee_id' => $assignment->employee_id]) }}">Back to History</a></div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Employee</span><strong>{{ $assignment->employee->name }}</strong></div>
    <div class="card stat"><span class="muted">Product</span><strong>{{ $assignment->product->name }}</strong></div>
    <div class="card stat"><span class="muted">Issued</span><strong>{{ $assignment->quantity }}</strong></div>
    <div class="card stat"><span class="muted">Returned</span><strong>{{ $assignment->returnedQuantity() }}</strong></div>
    <div class="card stat"><span class="muted">Currently Holding</span><strong>{{ $assignment->outstandingQuantity() }}</strong></div>
    <div class="card stat"><span class="muted">Unit Value</span><strong>{{ number_format((float)$assignment->unit_price, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Issued Value</span><strong>{{ number_format((float)$assignment->total, 2) }}</strong></div>
    <div class="card stat"><span class="muted">Holding Value</span><strong>{{ number_format($assignment->outstandingValue(), 2) }}</strong></div>
    <div class="card stat"><span class="muted">Source</span><strong>{{ $assignment->source_condition === 'used' ? 'Used Stock' : 'New Stock' }}</strong></div>
</div>

<section class="card" style="margin-bottom:16px">
    <h2>Handover Details</h2>
    <div class="form-grid">
        <div><span class="muted">Issue Date</span><strong>{{ $assignment->assigned_at->format('Y-m-d') }}</strong></div>
        <div><span class="muted">Warehouse</span><strong>{{ $assignment->warehouse->name }}</strong></div>
        <div><span class="muted">Serials</span><strong>{{ $assignment->serial_numbers ?: 'N/A' }}</strong></div>
        <div><span class="muted">Serial-less Qty</span><strong>{{ $assignment->serialless_quantity }}</strong></div>
        <div><span class="muted">Purpose / Location</span><strong>{{ $assignment->purpose ?? 'N/A' }}</strong></div>
        <div><span class="muted">Issued By</span><strong>{{ $assignment->issuedBy?->name ?? 'N/A' }}</strong></div>
        <div class="full"><span class="muted">Note</span><div>{{ $assignment->note ?? 'N/A' }}</div></div>
    </div>
</section>

@if ($assignment->outstandingQuantity() > 0)
<section class="card" style="margin-bottom:16px">
    <h2>Receive Return</h2>
    <div class="muted" style="margin-bottom:10px">Returned items will enter separate Used Stock and will not be mixed with new/saleable stock.</div>
    <form method="post" action="{{ route('in-house-use.returns.store', $assignment) }}" class="form-grid" id="assetReturnForm">
        @csrf
        <div><label>Return Date</label><input type="date" name="returned_at" value="{{ old('returned_at', now()->toDateString()) }}" required></div>
        <div><label>Return to Warehouse</label><select name="warehouse_id" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string)old('warehouse_id') === (string)$warehouse->id || (!old('warehouse_id') && $warehouse->is_default))>{{ $warehouse->name }}</option>@endforeach</select></div>
        <div><label>Return Quantity</label><input type="number" name="quantity" id="returnQuantity" min="1" max="{{ $assignment->outstandingQuantity() }}" value="{{ old('quantity') }}" required></div>
        @if ($assignment->product->track_serial_numbers)
            <div><label>Serial-less Return Qty</label><input type="number" name="serialless_quantity" id="returnSerialless" min="0" max="{{ $outstandingSerialless }}" value="{{ old('serialless_quantity', 0) }}"></div>
            <div class="full">
                <label>Returned Serials</label>
                <textarea name="serial_numbers" id="returnSerials" rows="2" placeholder="Select returned serials below">{{ old('serial_numbers') }}</textarea>
                <div class="actions" id="returnSerialOptions" style="margin-top:8px">
                    @foreach ($outstandingSerials as $serial)
                        <button type="button" class="btn light return-serial-option" data-serial="{{ $serial }}">{{ $serial }}</button>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="full"><label>Return Note</label><textarea name="note" rows="2" placeholder="Condition or other note">{{ old('note') }}</textarea></div>
        <div class="full actions"><button class="btn" type="submit">Receive into Used Stock</button></div>
    </form>
</section>
@endif

<section class="card">
    <h2>Return History</h2>
    <table>
        <thead><tr><th>Date</th><th>Warehouse</th><th>Quantity</th><th>Return Value</th><th>Serials</th><th>Serial-less</th><th>Note</th><th>Received By</th></tr></thead>
        <tbody>
        @forelse ($assignment->returns->sortByDesc('returned_at') as $return)
            <tr><td>{{ $return->returned_at->format('Y-m-d') }}</td><td>{{ $return->warehouse->name }}</td><td>{{ $return->quantity }}</td><td>{{ number_format($return->quantity * (float)$assignment->unit_price, 2) }}</td><td>{{ $return->serial_numbers ?: 'N/A' }}</td><td>{{ $return->serialless_quantity }}</td><td>{{ $return->note ?? 'N/A' }}</td><td>{{ $return->receivedBy?->name ?? 'N/A' }}</td></tr>
        @empty
            <tr><td colspan="8">No return recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

@if ($assignment->product->track_serial_numbers && $assignment->outstandingQuantity() > 0)
<script>
const returnSerialInput = document.getElementById('returnSerials');
const returnSeriallessInput = document.getElementById('returnSerialless');
const returnQuantityInput = document.getElementById('returnQuantity');
function selectedReturnSerials() { return [...new Set(returnSerialInput.value.split(/[\r\n,]+/).map(value => value.trim()).filter(Boolean))]; }
function syncReturnForm() {
    const selected = selectedReturnSerials();
    document.querySelectorAll('.return-serial-option').forEach(button => button.classList.toggle('secondary', selected.includes(button.dataset.serial)));
    returnQuantityInput.value = selected.length + (parseInt(returnSeriallessInput.value || '0', 10) || 0) || '';
}
document.getElementById('returnSerialOptions').addEventListener('click', event => {
    const button = event.target.closest('.return-serial-option');
    if (!button) return;
    const current = selectedReturnSerials();
    returnSerialInput.value = current.includes(button.dataset.serial) ? current.filter(serial => serial !== button.dataset.serial).join(', ') : [...current, button.dataset.serial].join(', ');
    syncReturnForm();
});
[returnSerialInput, returnSeriallessInput].forEach(input => input.addEventListener('input', syncReturnForm));
syncReturnForm();
</script>
@endif
@endsection
