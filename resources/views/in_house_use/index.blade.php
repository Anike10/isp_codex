@extends('layouts.app')

@section('content')
<style>
    .asset-serial-options { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .asset-serial-option { border:1px solid var(--line); border-radius:6px; background:#fff; padding:5px 8px; cursor:pointer; }
    .asset-serial-option.is-selected { border-color:#116149; background:#edf8f4; color:#0f513e; }
</style>

<div class="topbar">
    <div>
        <h1>In-house Use</h1>
        <div class="muted">Issue products to employees, receive returns, and keep returned items in separate used stock</div>
    </div>
    <a class="btn light" href="{{ route('products.index') }}">Products</a>
</div>

<section class="card" style="margin-bottom:16px">
    <h2>Issue Product to Employee</h2>
    <form method="post" action="{{ route('in-house-use.store') }}" class="form-grid" id="assetIssueForm">
        @csrf
        <div>
            <label>Employee</label>
            <select name="employee_id" required>
                <option value="">Select employee</option>
                @foreach ($employees->where('status', 'active') as $employee)
                    <option value="{{ $employee->id }}" @selected((string) old('employee_id', request('employee_id')) === (string) $employee->id)>{{ $employee->name }}{{ $employee->designation ? ' - '.$employee->designation : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Product</label>
            <select name="product_id" id="assetProduct" required>
                <option value="">Select product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('product_id', request('product_id')) === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Stock Type</label>
            <select name="source_condition" id="assetCondition" required>
                <option value="new" @selected(old('source_condition', 'new') === 'new')>New Stock</option>
                <option value="used" @selected(old('source_condition') === 'used')>Returned Used Stock</option>
            </select>
        </div>
        <div>
            <label>From Warehouse</label>
            <select name="warehouse_id" id="assetWarehouse" required>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id || (!old('warehouse_id') && $warehouse->is_default))>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <span class="muted" id="assetAvailability">Select a product to see available stock.</span>
        </div>
        <div>
            <label>Issue Date</label>
            <input type="date" name="assigned_at" value="{{ old('assigned_at', now()->toDateString()) }}" required>
        </div>
        <div>
            <label>Purpose / Location</label>
            <input name="purpose" value="{{ old('purpose') }}" placeholder="Office desk, field team, POP, etc.">
        </div>
        <div>
            <label>Quantity</label>
            <input type="number" name="quantity" id="assetQuantity" min="1" value="{{ old('quantity') }}" required>
        </div>
        <div id="assetSeriallessField" hidden>
            <label>Serial-less Qty</label>
            <input type="number" name="serialless_quantity" id="assetSerialless" min="0" value="{{ old('serialless_quantity', 0) }}">
        </div>
        <div class="full" id="assetSerialField" hidden>
            <label>Serial Numbers</label>
            <textarea name="serial_numbers" id="assetSerials" rows="2" placeholder="Click available serials below, or enter 1001-1010, 1020">{{ old('serial_numbers') }}</textarea>
            <div class="asset-serial-options" id="assetSerialOptions"></div>
        </div>
        <div class="full">
            <label>Note</label>
            <textarea name="note" rows="2" placeholder="Optional handover note">{{ old('note') }}</textarea>
        </div>
        <div class="full actions">
            <button class="btn" type="submit">Issue to Employee</button>
        </div>
    </form>
</section>

<section class="card" style="margin-bottom:16px">
    <h2>Employee Asset Summary</h2>
    <table>
        <thead><tr><th>Employee</th><th>Designation</th><th>Products</th><th>Total Issued</th><th>Returned</th><th>Currently Holding</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($employeeSummaries as $employee)
            @php
                $issued = (int) $employee->assetAssignments->sum('quantity');
                $returned = (int) $employee->assetAssignments->flatMap->returns->sum('quantity');
                $holding = max(0, $issued - $returned);
                $productCount = $employee->assetAssignments->pluck('product_id')->unique()->count();
            @endphp
            <tr>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->designation ?? 'N/A' }}</td>
                <td>{{ $productCount }}</td>
                <td>{{ $issued }}</td>
                <td>{{ $returned }}</td>
                <td><span class="badge {{ $holding > 0 ? 'pending' : '' }}">{{ $holding }}</span></td>
                <td><a class="btn light" href="{{ route('in-house-use.index', ['employee_id' => $employee->id]) }}">View Items</a></td>
            </tr>
        @empty
            <tr><td colspan="7">No employee asset issue recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card" style="margin-bottom:16px">
    <h2>Returned Used Stock</h2>
    <div class="muted" style="margin-bottom:10px">This stock is kept separately from new/saleable stock and can be issued again to an employee.</div>
    <table>
        <thead><tr><th>Product</th><th>Warehouse</th><th>Used Qty Available</th></tr></thead>
        <tbody>
        @forelse ($usedStocks as $stock)
            <tr><td>{{ $stock->product->name }} ({{ $stock->product->sku }})</td><td>{{ $stock->warehouse->name }}</td><td>{{ $stock->quantity }}</td></tr>
        @empty
            <tr><td colspan="3">No returned used stock available.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<div class="topbar">
    <div><h2>Issue & Return History</h2><div class="muted">Filter by employee or current return status</div></div>
</div>
<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Search Product</label><input name="search" value="{{ request('search') }}" placeholder="Product name or SKU"></div>
    <div><label>Employee</label><select name="employee_id"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int) request('employee_id') === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
    <div><label>Status</label><select name="status"><option value="">All</option><option value="outstanding" @selected(request('status') === 'outstanding')>Currently Holding</option><option value="returned" @selected(request('status') === 'returned')>Fully Returned</option></select></div>
    <div class="full actions"><button class="btn secondary" type="submit">Filter</button><a class="btn light" href="{{ route('in-house-use.index') }}">Reset</a></div>
</form>

@include('partials.per_page')
<table>
    <thead><tr><th>Date</th><th>Employee</th><th>Product</th><th>Source</th><th>Serials</th><th>Issued</th><th>Returned</th><th>Holding</th><th>Purpose</th><th>Action</th></tr></thead>
    <tbody>
    @forelse ($assignments as $assignment)
        <tr data-href="{{ route('in-house-use.show', $assignment) }}">
            <td>{{ $assignment->assigned_at->format('Y-m-d') }}</td>
            <td>{{ $assignment->employee->name }}</td>
            <td>{{ $assignment->product->name }}<div class="muted">{{ $assignment->product->sku }}</div></td>
            <td>{{ $assignment->source_condition === 'used' ? 'Used Stock' : 'New Stock' }}<div class="muted">{{ $assignment->warehouse->name }}</div></td>
            <td>{{ $assignment->serial_numbers ?: ($assignment->serialless_quantity.' serial-less') }}</td>
            <td>{{ $assignment->quantity }}</td>
            <td>{{ $assignment->returnedQuantity() }}</td>
            <td><span class="badge {{ $assignment->outstandingQuantity() > 0 ? 'pending' : '' }}">{{ $assignment->outstandingQuantity() }}</span></td>
            <td>{{ $assignment->purpose ?? 'N/A' }}</td>
            <td><a class="btn light" href="{{ route('in-house-use.show', $assignment) }}">View / Return</a></td>
        </tr>
    @empty
        <tr><td colspan="10">No issue record found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $assignments->links() }}</div>

<script>
const assetProducts = @json($productOptions);
const productSelect = document.getElementById('assetProduct');
const conditionSelect = document.getElementById('assetCondition');
const warehouseSelect = document.getElementById('assetWarehouse');
const quantityInput = document.getElementById('assetQuantity');
const serialInput = document.getElementById('assetSerials');
const seriallessInput = document.getElementById('assetSerialless');
const serialField = document.getElementById('assetSerialField');
const seriallessField = document.getElementById('assetSeriallessField');
const serialOptions = document.getElementById('assetSerialOptions');
const availability = document.getElementById('assetAvailability');

function selectedAssetProduct() {
    return assetProducts.find(product => String(product.id) === productSelect.value);
}

function expandedSerials(value) {
    return [...new Set(value.split(/[\r\n,]+/).map(part => part.trim()).filter(Boolean).flatMap(part => {
        const match = part.match(/^(.*?)(\d+)\s*(?:-|to)\s*(.*?)(\d+)$/i);
        if (!match) return [part];
        const startPrefix = match[1], endPrefix = match[3] || startPrefix;
        const start = Number(match[2]), end = Number(match[4]);
        if (startPrefix !== endPrefix || end < start || end - start > 999) return [part];
        const width = Math.max(match[2].length, match[4].length);
        return Array.from({length: end - start + 1}, (_, index) => startPrefix + String(start + index).padStart(width, '0'));
    }))];
}

function syncAssetIssueForm() {
    const product = selectedAssetProduct();
    const condition = conditionSelect.value;
    const warehouseId = warehouseSelect.value;
    const stockMap = condition === 'used' ? product?.used_stocks : product?.new_stocks;
    const serialMap = condition === 'used' ? product?.used_serials : product?.new_serials;
    const stock = Number(stockMap?.[warehouseId] || 0);
    const availableSerials = serialMap?.[warehouseId] || [];
    const tracksSerials = Boolean(product?.track_serials);

    availability.textContent = product ? `Available ${condition} stock: ${stock}` : 'Select a product to see available stock.';
    quantityInput.max = stock || '';
    serialField.hidden = !tracksSerials;
    seriallessField.hidden = !tracksSerials;
    serialOptions.innerHTML = '';

    if (!tracksSerials) return;

    const selected = expandedSerials(serialInput.value);
    availableSerials.forEach(serial => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'asset-serial-option' + (selected.includes(serial) ? ' is-selected' : '');
        button.textContent = serial;
        button.addEventListener('click', () => {
            const current = expandedSerials(serialInput.value);
            serialInput.value = current.includes(serial) ? current.filter(item => item !== serial).join(', ') : [...current, serial].join(', ');
            syncAssetIssueForm();
        });
        serialOptions.appendChild(button);
    });

    quantityInput.value = expandedSerials(serialInput.value).length + (parseInt(seriallessInput.value || '0', 10) || 0) || '';
}

[productSelect, conditionSelect, warehouseSelect, serialInput, seriallessInput].forEach(input => {
    input.addEventListener('change', syncAssetIssueForm);
    input.addEventListener('input', syncAssetIssueForm);
});
syncAssetIssueForm();
</script>
@endsection
