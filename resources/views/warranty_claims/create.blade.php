@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>New Warranty Claim</h1><div class="muted">Create from a sold serial when possible for clean customer asset history.</div></div>
    <a class="btn light" href="{{ route('warranty-claims.index') }}">Back</a>
</div>

@if ($selectedSerial)
    <section class="card" style="margin-bottom:16px">
        <h2>Selected Asset</h2>
        <p><strong>Customer:</strong> {{ $selectedSerial->customer?->name ?? 'N/A' }}</p>
        <p><strong>Product:</strong> {{ $selectedSerial->product?->name ?? 'N/A' }}</p>
        <p><strong>Serial:</strong> {{ $selectedSerial->serial_number }}</p>
        <p><strong>Warranty Until:</strong> {{ $selectedSerial->warranty_until?->format('Y-m-d') ?? 'No warranty' }}</p>
    </section>
@endif

<style>
    .lookup-field { position:relative; }
    .lookup-suggestions {
        display:none;
        position:absolute;
        top:calc(100% + 6px);
        left:0;
        right:0;
        z-index:30;
        max-height:240px;
        overflow:auto;
        padding:6px;
        background:white;
        border:1px solid var(--line);
        border-radius:8px;
        box-shadow:0 14px 28px rgba(15, 23, 42, .16);
    }
    .lookup-suggestions.is-open { display:grid; gap:4px; }
    .lookup-option {
        border:0;
        width:100%;
        padding:9px 10px;
        border-radius:6px;
        background:white;
        color:var(--ink);
        text-align:left;
        cursor:pointer;
    }
    .lookup-option:hover, .lookup-option.is-active { background:#eef4fb; }
    .lookup-option strong, .lookup-option span { display:block; }
    .lookup-option span { margin-top:3px; color:var(--muted); font-size:12px; }
    .asset-summary {
        display:none;
        padding:10px 12px;
        border:1px solid var(--line);
        border-radius:6px;
        background:#f8fafc;
    }
    .asset-summary.is-visible { display:block; }
</style>

<form method="post" action="{{ route('warranty-claims.store') }}" class="card form-grid">
    @csrf
    <input type="hidden" name="product_serial_id" id="product_serial_id" value="{{ old('product_serial_id', $selectedSerial?->id) }}">
    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $selectedSerial?->customer_id) }}">
    <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id', $selectedSerial?->product_id) }}">

    <div class="lookup-field full">
        <label>Serial Number</label>
        <input id="serial_search" value="{{ old('product_serial_query', $selectedSerial?->serial_number) }}" autocomplete="off" placeholder="Type product serial number">
        <div class="lookup-suggestions" id="serial_suggestions"></div>
    </div>
    <div class="asset-summary full" id="asset_summary"></div>

    <div class="lookup-field">
        <label>Customer</label>
        <input id="customer_search" required autocomplete="off" placeholder="Type customer name or phone">
        <div class="lookup-suggestions" id="customer_suggestions"></div>
    </div>
    <div class="lookup-field">
        <label>Product</label>
        <input id="product_search" autocomplete="off" placeholder="Auto-filled from serial or type product">
        <div class="lookup-suggestions" id="product_suggestions"></div>
    </div>
    <div><label>Claim Date</label><input type="date" name="claim_date" value="{{ old('claim_date', now()->toDateString()) }}" required></div>
    <div>
        <label>Action Type</label>
        <select name="action_type" required>
            @foreach ($actionTypes as $type)
                <option value="{{ $type }}" @selected(old('action_type', 'repair') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Assigned To</label>
        <select name="assigned_to">
            <option value="">Not assigned</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((int) old('assigned_to') === $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Vendor</label>
        <select name="vendor_id">
            <option value="">No vendor</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected((int) old('vendor_id') === $vendor->id)>{{ $vendor->name }} - {{ $vendor->phone }}</option>
            @endforeach
        </select>
    </div>
    <div class="full">
        <label>Problem Description</label>
        <textarea name="problem_description" required placeholder="Describe the issue, customer complaint, physical condition, adapter/power status, etc.">{{ old('problem_description') }}</textarea>
    </div>
    <div>
        <label>Service / Repair Charge</label>
        <input type="number" name="service_charge" min="0" step="0.01" value="{{ old('service_charge') }}" placeholder="Leave blank if not decided">
        <span class="muted">Use this when the repair/service should be tracked as income or due.</span>
    </div>
    <div>
        <label>Create Due Invoice Now</label>
        <select name="create_service_invoice">
            <option value="0" @selected(old('create_service_invoice', '0') === '0')>No, keep as estimate</option>
            <option value="1" @selected(old('create_service_invoice') === '1')>Yes, create due invoice</option>
        </select>
        <span class="muted">If yes, this amount will appear in invoice due and party ledger.</span>
    </div>
    <div class="full">
        <label>Service Invoice Note</label>
        <input name="service_note" value="{{ old('service_note') }}" placeholder="Repair work, diagnosis fee, parts/service note">
    </div>
    <div class="full"><button class="btn" type="submit">Create Claim</button></div>
</form>

<script>
const customers = {{ Illuminate\Support\Js::from($customerOptions) }};
const products = {{ Illuminate\Support\Js::from($productOptions) }};
const serials = {{ Illuminate\Support\Js::from($serialOptions) }};

const serialId = document.getElementById('product_serial_id');
const customerId = document.getElementById('customer_id');
const productId = document.getElementById('product_id');
const serialSearch = document.getElementById('serial_search');
const customerSearch = document.getElementById('customer_search');
const productSearch = document.getElementById('product_search');
const assetSummary = document.getElementById('asset_summary');

const normalize = value => (value || '').toString().trim().toLowerCase();
const escapeHtml = value => (value || '').toString().replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
}[char]));
const customerLabel = customer => customer ? [customer.name, customer.phone].filter(Boolean).join(' - ') : '';
const productLabel = product => product ? [product.name, product.sku, product.brand].filter(Boolean).join(' - ') : '';
const serialLabel = serial => [
    serial.serial_number,
    serial.product?.name,
    serial.customer?.name,
    serial.customer?.phone,
].filter(Boolean).join(' - ');

function setCustomer(customer) {
    customerId.value = customer?.id || '';
    customerSearch.value = customerLabel(customer);
}

function setProduct(product) {
    productId.value = product?.id || '';
    productSearch.value = productLabel(product);
}

function renderAssetSummary(serial) {
    if (! serial) {
        assetSummary.classList.remove('is-visible');
        assetSummary.innerHTML = '';
        return;
    }

    assetSummary.innerHTML = `
        <strong>Selected Asset</strong><br>
        Product: ${escapeHtml(serial.product?.name || 'N/A')}<br>
        Serial: ${escapeHtml(serial.serial_number)}<br>
        Customer: ${escapeHtml(customerLabel(serial.customer) || 'N/A')}<br>
        Warranty: ${escapeHtml(serial.warranty_until || 'No warranty')}<br>
        Invoice: ${escapeHtml(serial.invoice_no || 'N/A')}
    `;
    assetSummary.classList.add('is-visible');
}

function chooseSerial(serial) {
    serialId.value = serial.id;
    serialSearch.value = serial.serial_number;
    setCustomer(serial.customer);
    setProduct(serial.product);
    renderAssetSummary(serial);
    closeSuggestions();
}

function setupLookup(input, suggestions, source, config) {
    const render = () => {
        const query = normalize(input.value);
        const matches = source
            .filter(item => ! query || normalize(config.searchText(item)).includes(query))
            .slice(0, 12);

        suggestions.innerHTML = '';
        if (! matches.length) {
            suggestions.classList.remove('is-open');
            return;
        }

        matches.forEach(item => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'lookup-option';
            button.innerHTML = `<strong>${escapeHtml(config.title(item))}</strong><span>${escapeHtml(config.subtitle(item))}</span>`;
            button.addEventListener('click', () => config.select(item));
            suggestions.appendChild(button);
        });
        suggestions.classList.add('is-open');
    };

    input.addEventListener('input', () => {
        config.clear?.();
        render();
    });
    input.addEventListener('focus', render);
}

function closeSuggestions() {
    document.querySelectorAll('.lookup-suggestions').forEach(element => element.classList.remove('is-open'));
}

setupLookup(serialSearch, document.getElementById('serial_suggestions'), serials, {
    searchText: serialLabel,
    title: serial => serial.serial_number,
    subtitle: serial => [serial.product?.name, customerLabel(serial.customer), serial.warranty_until ? `Warranty ${serial.warranty_until}` : 'No warranty'].filter(Boolean).join(' | '),
    select: chooseSerial,
    clear: () => {
        serialId.value = '';
        renderAssetSummary(null);
    },
});

setupLookup(customerSearch, document.getElementById('customer_suggestions'), customers, {
    searchText: customer => [customer.name, customer.phone, customer.address].filter(Boolean).join(' '),
    title: customer => customer.name,
    subtitle: customer => [customer.phone, customer.address].filter(Boolean).join(' | '),
    select: customer => {
        setCustomer(customer);
        closeSuggestions();
    },
    clear: () => customerId.value = '',
});

setupLookup(productSearch, document.getElementById('product_suggestions'), products, {
    searchText: product => [product.name, product.sku, product.brand].filter(Boolean).join(' '),
    title: product => product.name,
    subtitle: product => [product.sku, product.brand].filter(Boolean).join(' | '),
    select: product => {
        setProduct(product);
        closeSuggestions();
    },
    clear: () => productId.value = '',
});

document.addEventListener('click', event => {
    if (! event.target.closest('.lookup-field')) {
        closeSuggestions();
    }
});

const initialCustomer = customers.find(customer => String(customer.id) === String(customerId.value));
const initialProduct = products.find(product => String(product.id) === String(productId.value));
const initialSerial = serials.find(serial => String(serial.id) === String(serialId.value));
setCustomer(initialCustomer);
setProduct(initialProduct || initialSerial?.product);
renderAssetSummary(initialSerial);
</script>
@endsection
