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

<form method="post" action="{{ route('warranty-claims.store') }}" class="card form-grid">
    @csrf
    <input type="hidden" name="product_serial_id" value="{{ old('product_serial_id', $selectedSerial?->id) }}">

    <div>
        <label>Customer</label>
        <select name="customer_id" required>
            <option value="">Select customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) old('customer_id', $selectedSerial?->customer_id) === $customer->id)>{{ $customer->name }} - {{ $customer->phone }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Manual Product</label>
        <select name="product_id">
            <option value="">Use selected serial product / manual only</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((int) old('product_id', $selectedSerial?->product_id) === $product->id)>{{ $product->name }} - {{ $product->sku }}</option>
            @endforeach
        </select>
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
    <div class="full"><button class="btn" type="submit">Create Claim</button></div>
</form>
@endsection
