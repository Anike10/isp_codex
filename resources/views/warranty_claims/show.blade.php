@extends('layouts.app')

@section('content')
@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canManageWarranty = auth()->user()?->hasPermission('manage_warranty_claims') || auth()->user()?->hasPermission('manage_products');
    $statusText = str_replace('_', ' ', ucfirst($claim->status));
    $statusClass = in_array($claim->status, ['closed', 'delivered', 'replaced'], true) ? 'active' : ($claim->status === 'rejected' ? 'failed' : 'pending');
    $warrantyClass = $claim->warranty_status === 'in_warranty' ? 'active' : ($claim->warranty_status === 'expired' ? 'due' : 'inactive');
    $actionType = $claim->action_type ?: 'repair';
    $quickActions = [
        'pending' => [
            ['label' => 'Receive Product', 'status' => 'received', 'action' => $actionType, 'note' => 'Product received from customer.'],
        ],
        'received' => [
            ['label' => 'Start Diagnosis', 'status' => 'diagnosing', 'action' => $actionType, 'note' => 'Diagnosis started.'],
        ],
        'diagnosing' => [
            ['label' => 'Start Repair', 'status' => 'repairing', 'action' => 'repair', 'note' => 'Repair work started.'],
            ['label' => 'Ready for Delivery', 'status' => 'ready', 'action' => $actionType, 'note' => 'Work completed and ready for delivery.'],
        ],
        'repairing' => [
            ['label' => 'Ready for Delivery', 'status' => 'ready', 'action' => 'repair', 'note' => 'Repair completed and ready for delivery.'],
        ],
        'sent_to_vendor' => [
            ['label' => 'Vendor Returned', 'status' => 'vendor_returned', 'action' => 'vendor_return', 'note' => 'Product returned from vendor.'],
        ],
        'vendor_returned' => [
            ['label' => 'Ready for Delivery', 'status' => 'ready', 'action' => $actionType, 'note' => 'Vendor return checked and ready for delivery.'],
        ],
        'ready' => [
            ['label' => 'Deliver and Close', 'status' => 'delivered', 'action' => $actionType, 'note' => 'Product delivered to customer.'],
        ],
        'paid_service' => [
            ['label' => 'Deliver and Close', 'status' => 'delivered', 'action' => 'paid_service', 'note' => 'Paid service delivered to customer.'],
        ],
    ][$claim->status] ?? [];
    $steps = [
        'pending' => ['Pending', ['pending']],
        'received' => ['Received', ['received']],
        'work' => ['Work', ['diagnosing', 'repairing', 'sent_to_vendor', 'vendor_returned', 'paid_service']],
        'ready' => ['Ready', ['ready']],
        'done' => ['Done', ['delivered', 'replaced', 'closed']],
    ];
@endphp

<style>
    .claim-hero { display:grid; grid-template-columns:minmax(0, 1.5fr) minmax(260px, .8fr); gap:16px; align-items:stretch; margin-bottom:16px; }
    .claim-title { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:8px; }
    .claim-facts { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; margin-top:14px; }
    .claim-fact { border:1px solid var(--line); border-radius:8px; padding:10px; background:#fbfcfe; }
    .claim-fact span { display:block; color:var(--muted); font-size:12px; margin-bottom:4px; }
    .workflow-steps { display:grid; grid-template-columns:repeat(5, minmax(0, 1fr)); gap:6px; margin-top:12px; }
    .workflow-step { border:1px solid var(--line); border-radius:8px; padding:9px 6px; text-align:center; color:var(--muted); background:#f8fafc; font-weight:700; font-size:12px; }
    .workflow-step.active { border-color:#116149; background:#ecfdf3; color:#027a48; }
    .quick-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; }
    .quick-grid form { display:grid; gap:8px; }
    .info-list { display:grid; gap:8px; }
    .info-row { display:grid; grid-template-columns:140px minmax(0, 1fr); gap:8px; padding:8px 0; border-bottom:1px solid var(--line); }
    .info-row:last-child { border-bottom:0; }
    .history { display:grid; gap:10px; }
    .history-row { display:grid; grid-template-columns:160px minmax(0, 1fr); gap:12px; padding:12px; border:1px solid var(--line); border-radius:8px; background:#fff; }
    .history-time { color:var(--muted); line-height:1.5; }
    .history-body h3 { margin:0 0 5px; font-size:16px; }
    .history-details { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:6px 12px; margin-top:8px; }
    .history-details div { border-top:1px solid #eef2f7; padding-top:6px; }
    @media (max-width: 860px) {
        .claim-hero, .history-row, .info-row { grid-template-columns:1fr; }
        .workflow-steps, .claim-facts, .history-details { grid-template-columns:1fr; }
    }
</style>

<div class="topbar">
    <div>
        <h1>{{ $claim->claim_no }}</h1>
        <div class="muted">{{ $claim->customer->name }} - {{ $claim->customer->phone }}</div>
        <div class="claim-title">
            <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
            <span class="badge {{ $warrantyClass }}">{{ str_replace('_', ' ', $claim->warranty_status) }}</span>
            <span class="badge pending">{{ str_replace('_', ' ', $claim->action_type) }}</span>
        </div>
    </div>
    <div class="actions">
        <a class="btn light" href="{{ route('warranty-claims.index') }}">Back</a>
        @if ($claim->invoice && $canOpenInvoices)
            <a class="btn secondary" href="{{ route('invoices.show', $claim->invoice) }}">Invoice</a>
        @endif
    </div>
</div>

<section class="claim-hero">
    <div class="card">
        <h2>Current Job</h2>
        <div class="claim-facts">
            <div class="claim-fact"><span>Product</span><strong>{{ $claim->product?->name ?? 'Manual claim' }}</strong></div>
            <div class="claim-fact"><span>Serial</span><strong>{{ $claim->productSerial?->serial_number ?? 'N/A' }}</strong></div>
            <div class="claim-fact"><span>Claim Date</span><strong>{{ $claim->claim_date?->format('Y-m-d') }}</strong></div>
            <div class="claim-fact"><span>Warranty Until</span><strong>{{ $claim->productSerial?->warranty_until?->format('Y-m-d') ?? 'No warranty' }}</strong></div>
            <div class="claim-fact"><span>Assigned</span><strong>{{ $claim->assignedUser?->name ?? 'Not assigned' }}</strong></div>
            <div class="claim-fact"><span>Vendor</span><strong>{{ $claim->vendor?->name ?? 'N/A' }}</strong></div>
            <div class="claim-fact"><span>Service Charge</span><strong>{{ number_format($claim->service_charge, 2) }}</strong></div>
            <div class="claim-fact"><span>Accounting</span><strong>{{ $claim->serviceInvoice ? 'Invoice '.$claim->serviceInvoice->invoice_no.' - '.$claim->serviceInvoice->status : (((float) $claim->service_charge > 0) ? 'Estimate only' : 'No charge') }}</strong></div>
        </div>
        <div class="workflow-steps">
            @foreach ($steps as [$label, $statuses])
                <div class="workflow-step {{ in_array($claim->status, $statuses, true) ? 'active' : '' }}">{{ $label }}</div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h2>Customer Problem</h2>
        <p style="white-space:pre-line">{{ $claim->problem_description }}</p>
        @if ($claim->diagnosis_note)
            <p><strong>Diagnosis:</strong><br><span style="white-space:pre-line">{{ $claim->diagnosis_note }}</span></p>
        @endif
        @if ($claim->resolution_note)
            <p><strong>Resolution:</strong><br><span style="white-space:pre-line">{{ $claim->resolution_note }}</span></p>
        @endif
    </div>
</section>

@if ($canManageWarranty)
    <section class="card" style="margin-bottom:16px">
        <h2>Quick Actions</h2>
        <div class="quick-grid">
            @foreach ($quickActions as $action)
                <form method="post" action="{{ route('warranty-claims.status', $claim) }}">
                    @csrf
                    <input type="hidden" name="status" value="{{ $action['status'] }}">
                    <input type="hidden" name="action_type" value="{{ $action['action'] }}">
                    <input type="hidden" name="note" value="{{ $action['note'] }}">
                    <button class="btn" type="submit">{{ $action['label'] }}</button>
                </form>
            @endforeach

            @if (! in_array($claim->status, ['sent_to_vendor', 'delivered', 'replaced', 'closed', 'rejected'], true))
                <form method="post" action="{{ route('warranty-claims.status', $claim) }}">
                    @csrf
                    <input type="hidden" name="status" value="sent_to_vendor">
                    <input type="hidden" name="action_type" value="vendor_return">
                    <select name="vendor_id" required>
                        <option value="">Select vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected($claim->vendor_id === $vendor->id)>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                    <input name="note" placeholder="Vendor note" value="Sent to vendor for warranty service.">
                    <button class="btn secondary" type="submit">Send To Vendor</button>
                </form>
            @endif

            @if (! in_array($claim->status, ['delivered', 'replaced', 'closed', 'rejected'], true))
                <form method="post" action="{{ route('warranty-claims.status', $claim) }}">
                    @csrf
                    <input type="hidden" name="status" value="rejected">
                    <input type="hidden" name="action_type" value="reject">
                    <input name="note" placeholder="Reject reason" required>
                    <button class="btn light" type="submit">Reject Claim</button>
                </form>
            @endif
        </div>
    </section>

    <div class="grid two" style="margin-bottom:16px">
        <section class="card">
            <h2>Replace Product</h2>
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
                @if ($claim->serviceInvoice)
                    <button class="btn secondary" type="button" disabled>Invoice Already Created</button>
                @else
                    <button class="btn secondary" type="submit">Create Service Invoice</button>
                @endif
            </form>
        </section>
    </div>

    <section class="card" style="margin-bottom:16px">
        <h2>Manual Update</h2>
        <form method="post" action="{{ route('warranty-claims.status', $claim) }}" class="form-grid">
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
            <div><label>Log Note</label><input name="note" placeholder="Short update note"></div>
            <div class="full"><label>Diagnosis</label><textarea name="diagnosis_note" rows="2">{{ $claim->diagnosis_note }}</textarea></div>
            <div><label>Resolution</label><textarea name="resolution_note" rows="2">{{ $claim->resolution_note }}</textarea></div>
            <div><label>Delivery Note</label><textarea name="delivery_note" rows="2">{{ $claim->delivery_note }}</textarea></div>
            <div class="full"><button class="btn" type="submit">Save Manual Update</button></div>
        </form>
    </section>
@endif

<div class="grid two" style="margin-bottom:16px">
    <section class="card">
        <h2>Final Details</h2>
        <div class="info-list">
            <div class="info-row"><span class="muted">Received</span><span>{{ $claim->received_at?->format('Y-m-d H:i') ?? 'Not received' }}</span></div>
            <div class="info-row"><span class="muted">Closed</span><span>{{ $claim->closed_at?->format('Y-m-d H:i') ?? 'Open' }}</span></div>
            <div class="info-row"><span class="muted">Service Charge</span><span>{{ number_format($claim->service_charge, 2) }}</span></div>
            <div class="info-row"><span class="muted">Delivery</span><span style="white-space:pre-line">{{ $claim->delivery_note ?: 'N/A' }}</span></div>
        </div>
    </section>
    <section class="card">
        <h2>Replacement / Service</h2>
        <div class="info-list">
            <div class="info-row"><span class="muted">Replacement</span><span>{{ $claim->replacementProductSerial?->serial_number ?? 'N/A' }}</span></div>
            <div class="info-row">
                <span class="muted">Service Invoice</span>
                <span>
                    @if ($claim->serviceInvoice)
                        @if ($canOpenInvoices)
                            <a href="{{ route('invoices.show', $claim->serviceInvoice) }}">{{ $claim->serviceInvoice->invoice_no }}</a>
                        @else
                            {{ $claim->serviceInvoice->invoice_no }}
                        @endif
                        - {{ number_format($claim->service_charge, 2) }}
                    @else
                        N/A
                    @endif
                </span>
            </div>
        </div>
    </section>
</div>

<section class="card">
    <h2>Work History</h2>
    <div class="history">
        @forelse ($timelineEvents as $event)
            <article class="history-row">
                <div class="history-time">
                    <strong>{{ $event['date']?->format('Y-m-d H:i') ?? 'N/A' }}</strong>
                    <div>{{ $event['actor'] }}</div>
                    <span class="badge pending">{{ $event['status'] }}</span>
                </div>
                <div class="history-body">
                    <h3>{{ $event['title'] }}</h3>
                    <div style="white-space:pre-line">{{ $event['note'] }}</div>
                    <div class="history-details">
                        @foreach ($event['details'] as $label => $value)
                            <div><span class="muted">{{ $label }}</span><br><strong>{{ $value }}</strong></div>
                        @endforeach
                    </div>
                </div>
            </article>
        @empty
            <p>No history yet.</p>
        @endforelse
    </div>
</section>
@endsection
