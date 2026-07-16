@extends('layouts.app')

@section('content')
<style>
    .fleet-hero{padding:26px;border-radius:18px;background:linear-gradient(135deg,#172554 0%,#1d4ed8 58%,#06b6d4 100%);color:#fff;box-shadow:0 18px 45px rgba(29,78,216,.2);margin-bottom:18px}
    .fleet-hero-top{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap}.fleet-hero h1{margin:8px 0 6px;font-size:clamp(24px,4vw,36px);line-height:1.15}.fleet-hero .muted{color:#dbeafe}.fleet-status{display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;font-size:12px;font-weight:900;letter-spacing:.06em;text-transform:uppercase;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.35)}
    .fleet-hero .btn{border-color:rgba(255,255,255,.55)}.fleet-hero .btn.light{background:#fff;color:#1e3a8a}.fleet-hero .btn.secondary{background:rgba(255,255,255,.13);color:#fff}.fleet-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:22px}.fleet-metric{padding:14px;border-radius:13px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18)}.fleet-metric span{display:block;color:#bfdbfe;font-size:12px;font-weight:800;text-transform:uppercase}.fleet-metric strong{display:block;margin-top:5px;font-size:20px}
    .fleet-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 22px}.fleet-detail-row{padding:15px 0;border-bottom:1px solid var(--line)}.fleet-detail-row span{display:block;color:var(--muted);font-size:12px;font-weight:800;text-transform:uppercase;margin-bottom:5px}.fleet-detail-row strong,.fleet-detail-row div{font-size:15px}.fleet-note{padding:16px;border-radius:12px;background:#f8fafc;border:1px solid var(--line);white-space:pre-wrap;line-height:1.65}.fleet-media{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,240px));gap:14px}.fleet-photo{display:block;padding:9px;border:1px solid var(--line);border-radius:14px;background:#fff}.fleet-photo img{width:100%;height:170px;object-fit:cover;border-radius:9px}.fleet-photo span{display:block;margin-top:8px;font-weight:700;overflow-wrap:anywhere}.draft-callout{padding:13px 15px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;margin-bottom:16px}.final-callout{padding:13px 15px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;margin-bottom:16px}
    @media(max-width:780px){.fleet-metrics,.fleet-detail-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.fleet-metrics,.fleet-detail-grid{grid-template-columns:1fr}.fleet-hero{padding:20px}}
</style>

<section class="fleet-hero">
    <div class="fleet-hero-top">
        <div>
            <span class="fleet-status">{{ $maintenanceLog->isFinalized() ? 'Finalized' : 'Editable Draft' }}</span>
            <h1>{{ $maintenanceLog->item?->name ?? $maintenanceLog->work_name ?? 'General Maintenance' }}</h1>
            <div class="muted">{{ $maintenanceLog->vehicle->registration_no }} — {{ $maintenanceLog->vehicle->name }}</div>
        </div>
        <div class="actions">
            @if(! $maintenanceLog->isFinalized())
                <a class="btn light" href="{{ route('fleet.maintenance-logs.edit', $maintenanceLog) }}">Edit Draft</a>
                <form method="post" action="{{ route('fleet.maintenance-logs.finalize', $maintenanceLog) }}" onsubmit="return confirm('Finalize this maintenance record? It cannot be edited afterward.');">@csrf<button class="btn secondary">Final & Lock</button></form>
            @endif
            <a class="btn secondary" href="{{ route('fleet.show', $maintenanceLog->vehicle) }}">Back to Vehicle</a>
        </div>
    </div>
    <div class="fleet-metrics">
        <div class="fleet-metric"><span>Work Date</span><strong>{{ $maintenanceLog->service_date->format('Y-m-d') }}</strong></div>
        <div class="fleet-metric"><span>Action</span><strong>{{ \App\Models\VehicleMaintenanceLog::ACTIONS[$maintenanceLog->action] ?? ucfirst($maintenanceLog->action) }}</strong></div>
        <div class="fleet-metric"><span>Cost</span><strong>{{ number_format((float)$maintenanceLog->cost,2) }}</strong></div>
        <div class="fleet-metric"><span>Mileage</span><strong>{{ $maintenanceLog->mileage !== null ? number_format($maintenanceLog->mileage).' km' : 'N/A' }}</strong></div>
    </div>
</section>

@if($maintenanceLog->isFinalized())
    <div class="final-callout"><strong>Finalized and locked.</strong> Finalized by {{ $maintenanceLog->finalizer?->name ?? 'N/A' }} on {{ $maintenanceLog->finalized_at->format('Y-m-d h:i A') }}.</div>
@else
    <div class="draft-callout"><strong>This record is still a draft.</strong> You can edit it and every previous version will remain in Edit History. Finalize only after checking all information.</div>
@endif

<section class="card" style="margin-bottom:16px">
    <div class="topbar" style="margin-bottom:4px"><div><h2>Work Details</h2><div class="muted">Complete service and workshop information</div></div><a class="btn light" href="{{ route('fleet.reports.maintenance', ['vehicle_id'=>$maintenanceLog->vehicle_id]) }}">Maintenance Report</a></div>
    <div class="fleet-detail-grid">
        <div class="fleet-detail-row"><span>Scheduled Item</span><strong>{{ $maintenanceLog->item?->name ?? 'General / unscheduled repair' }}</strong></div>
        <div class="fleet-detail-row"><span>Work / Repair Name</span><strong>{{ $maintenanceLog->work_name ?? 'N/A' }}</strong></div>
        <div class="fleet-detail-row"><span>Workshop / Vendor</span><strong>{{ $maintenanceLog->vendor ?? 'N/A' }}</strong></div>
        <div class="fleet-detail-row"><span>Entered By</span><strong>{{ $maintenanceLog->creator?->name ?? 'N/A' }}</strong></div>
        <div class="fleet-detail-row"><span>Entry Time</span><strong>{{ $maintenanceLog->created_at?->format('Y-m-d h:i A') ?? 'N/A' }}</strong></div>
        <div class="fleet-detail-row"><span>Last Updated</span><strong>{{ $maintenanceLog->updated_at?->format('Y-m-d h:i A') ?? 'N/A' }}</strong></div>
    </div>
    <div style="margin-top:18px"><label>What Was Done</label><div class="fleet-note">{{ $maintenanceLog->details ?? 'N/A' }}</div></div>
</section>

<section class="card">
    <div class="topbar" style="margin-bottom:12px"><div><h2>Photos & Video</h2><div class="muted">Receipts, accounts and work evidence</div></div>@if($maintenanceLog->youtube_url)<a class="btn secondary" href="{{ $maintenanceLog->youtube_url }}" target="_blank" rel="noopener">Open YouTube Video</a>@endif</div>
    @if($maintenanceLog->photos->isNotEmpty())
        <div class="fleet-media">@foreach($maintenanceLog->photos as $index=>$photo)<a class="fleet-photo" href="{{ route('fleet.maintenance.photos.show',$photo) }}" target="_blank" rel="noopener"><img src="{{ route('fleet.maintenance.photos.show',$photo) }}" alt="Work photo {{ $index+1 }}"><span>Photo {{ $index+1 }} — {{ $photo->original_name }}</span></a>@endforeach</div>
    @elseif(!$maintenanceLog->youtube_url)<div class="muted">No photos or video were attached.</div>@endif
</section>

@include('partials.record_versions', ['versions'=>$versions])
@endsection
