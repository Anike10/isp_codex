@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>{{ $claim->claim_no }}</h1><div class="muted">{{ $claim->customer->name }} - {{ $claim->customer->phone }}</div></div>
    <div class="actions">
        <a class="btn light" href="{{ route('warranty-claims.index') }}">Back</a>
        @if ($claim->invoice)
            <a class="btn secondary" href="{{ route('invoices.show', $claim->invoice) }}">Invoice</a>
        @endif
    </div>
</div>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Status</span><strong>{{ str_replace('_', ' ', ucfirst($claim->status)) }}</strong></div>
    <div class="card stat"><span class="muted">Warranty</span><strong>{{ str_replace('_', ' ', $claim->warranty_status) }}</strong></div>
    <div class="card stat"><span class="muted">Action</span><strong>{{ str_replace('_', ' ', ucfirst($claim->action_type)) }}</strong></div>
    <div class="card stat"><span class="muted">Serial</span><strong>{{ $claim->productSerial?->serial_number ?? 'N/A' }}</strong></div>
</div>

<div class="grid two">
    <section class="card">
        <h2>Claim Details</h2>
        <p><strong>Product:</strong> {{ $claim->product?->name ?? 'Manual claim' }}</p>
        <p><strong>Warranty Until:</strong> {{ $claim->productSerial?->warranty_until?->format('Y-m-d') ?? 'No warranty' }}</p>
        <p><strong>Assigned:</strong> {{ $claim->assignedUser?->name ?? 'Not assigned' }}</p>
        <p><strong>Vendor:</strong> {{ $claim->vendor?->name ?? 'N/A' }}</p>
        <p><strong>Problem:</strong><br>{{ $claim->problem_description }}</p>
        @if ($claim->diagnosis_note)<p><strong>Diagnosis:</strong><br>{{ $claim->diagnosis_note }}</p>@endif
        @if ($claim->resolution_note)<p><strong>Resolution:</strong><br>{{ $claim->resolution_note }}</p>@endif
        @if ($claim->replacementProductSerial)<p><strong>Replacement:</strong> {{ $claim->replacementProductSerial->serial_number }}</p>@endif
        @if ($claim->serviceInvoice)<p><strong>Paid Service Invoice:</strong> <a href="{{ route('invoices.show', $claim->serviceInvoice) }}">{{ $claim->serviceInvoice->invoice_no }}</a></p>@endif
    </section>

    @if (auth()->user()?->hasPermission('manage_warranty_claims'))
        <section class="card">
            <h2>Update Claim</h2>
            <form method="post" action="{{ route('warranty-claims.status', $claim) }}" class="grid">
                @csrf
                <div>
                    <label>Status</label>
                    <select name="status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($claim->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Action Type</label>
                    <select name="action_type" required>
                        @foreach ($actionTypes as $type)
                            <option value="{{ $type }}" @selected($claim->action_type === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Vendor</label>
                    <select name="vendor_id">
                        <option value="">No vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected($claim->vendor_id === $vendor->id)>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Diagnosis</label><textarea name="diagnosis_note" rows="2">{{ $claim->diagnosis_note }}</textarea></div>
                <div><label>Resolution</label><textarea name="resolution_note" rows="2">{{ $claim->resolution_note }}</textarea></div>
                <div><label>Delivery Note</label><textarea name="delivery_note" rows="2">{{ $claim->delivery_note }}</textarea></div>
                <div><label>Log Note</label><textarea name="note" rows="2"></textarea></div>
                <button class="btn" type="submit">Save Update</button>
            </form>
        </section>
    @endif
</div>

@if (auth()->user()?->hasPermission('manage_warranty_claims'))
    <div class="grid two" style="margin-top:16px">
        <section class="card">
            <h2>Replacement</h2>
            <form method="post" action="{{ route('warranty-claims.replace', $claim) }}" class="grid">
                @csrf
                <div>
                    <label>Replacement Serial</label>
                    <select name="replacement_product_serial_id" required>
                        <option value="">Select in-stock serial</option>
                        @foreach ($replacementSerials as $serial)
                            <option value="{{ $serial->id }}">{{ $serial->serial_number }} - {{ $serial->product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Resolution Note</label><textarea name="resolution_note" rows="2"></textarea></div>
                <button class="btn secondary" type="submit">Complete Replacement</button>
            </form>
        </section>

        <section class="card">
            <h2>Paid Service</h2>
            <form method="post" action="{{ route('warranty-claims.service-invoice', $claim) }}" class="grid">
                @csrf
                <div><label>Service Charge</label><input type="number" name="service_charge" min="0" step="0.01" value="{{ old('service_charge', $claim->service_charge) }}" required></div>
                <div><label>Invoice Note</label><textarea name="note" rows="2"></textarea></div>
                <button class="btn secondary" type="submit">Create Paid Service Invoice</button>
            </form>
        </section>
    </div>
@endif

<section class="card" style="margin-top:16px">
    <h2>Timeline</h2>
    <table>
        <thead><tr><th>Date</th><th>From</th><th>To</th><th>Entry By</th><th>Note</th></tr></thead>
        <tbody>
        @forelse ($claim->logs as $log)
            <tr>
                <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $log->old_status ? str_replace('_', ' ', $log->old_status) : 'N/A' }}</td>
                <td>{{ str_replace('_', ' ', $log->new_status) }}</td>
                <td>{{ $log->entry_by ?? 'System' }}</td>
                <td>{{ $log->note ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No timeline yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
