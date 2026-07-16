@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Edit Maintenance Draft</h1><div class="muted">{{ $maintenanceLog->vehicle->registration_no }} — changes will be saved in Edit History</div></div><a class="btn light" href="{{ route('fleet.maintenance-logs.show',$maintenanceLog) }}">Cancel</a></div>
<div class="draft-callout" style="padding:13px 15px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;margin-bottom:16px"><strong>Draft record:</strong> Edit carefully, then use Final & Lock from the detail page when complete.</div>
<form method="post" action="{{ route('fleet.maintenance-logs.update',$maintenanceLog) }}" enctype="multipart/form-data" class="card form-grid">@csrf @method('put')
    <div><label>Scheduled Item (Optional)</label><select name="maintenance_item_id"><option value="">General / unscheduled repair</option>@foreach($maintenanceItems as $item)<option value="{{ $item->id }}" @selected((int)old('maintenance_item_id',$maintenanceLog->maintenance_item_id)===$item->id)>{{ $item->name }}</option>@endforeach</select></div>
    <div><label>Work / Repair Name</label><input name="work_name" value="{{ old('work_name',$maintenanceLog->work_name) }}" placeholder="Clutch repair, body work, electrical repair"></div>
    <div><label>Action</label><select name="action">@foreach(\App\Models\VehicleMaintenanceLog::ACTIONS as $key=>$label)<option value="{{ $key }}" @selected(old('action',$maintenanceLog->action)===$key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Work Date</label><input type="date" name="service_date" value="{{ old('service_date',$maintenanceLog->service_date->format('Y-m-d')) }}" required></div>
    <div><label>Mileage</label><input type="number" min="0" name="mileage" value="{{ old('mileage',$maintenanceLog->mileage) }}"></div>
    <div><label>Cost</label><input type="number" min="0" step="0.01" name="cost" value="{{ old('cost',$maintenanceLog->cost) }}" required></div>
    <div><label>Workshop / Vendor</label><input name="vendor" value="{{ old('vendor',$maintenanceLog->vendor) }}"></div>
    <div class="full"><label>What Was Done</label><textarea name="details" rows="4">{{ old('details',$maintenanceLog->details) }}</textarea></div>
    <div class="full"><label>YouTube Video Link</label><input type="url" name="youtube_url" value="{{ old('youtube_url',$maintenanceLog->youtube_url) }}" placeholder="https://youtu.be/..."></div>
    @if($maintenanceLog->photos->isNotEmpty())<div class="full"><label>Existing Photos</label><div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">@foreach($maintenanceLog->photos as $photo)<label class="card" style="padding:10px"><img src="{{ route('fleet.maintenance.photos.show',$photo) }}" alt="{{ $photo->original_name }}" style="width:100%;height:120px;object-fit:cover;border-radius:8px"><span style="display:flex;gap:8px;margin-top:8px"><input type="checkbox" name="remove_photo_ids[]" value="{{ $photo->id }}" style="width:auto"> Remove {{ $photo->original_name }}</span></label>@endforeach</div></div>@endif
    <div class="full"><label>Add More Photos</label><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple><div class="muted" style="margin-top:6px">Up to {{ \App\Services\FleetMaintenanceMediaService::MAX_PHOTO_COUNT }} photos per save; {{ $imageMaxMb }} MB each.</div></div>
    <div class="full actions"><button class="btn">Save Draft Changes</button><a class="btn light" href="{{ route('fleet.maintenance-logs.show',$maintenanceLog) }}">Cancel</a></div>
</form>
@endsection
