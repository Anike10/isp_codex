@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Vehicle Maintenance Report</h1><div class="muted">Checks, changes, services and repairs within a custom date range</div></div><a class="btn light" href="{{ route('fleet.index') }}">Vehicles</a></div>
@include('fleet.reports._nav')

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div><label>From Date</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>To Date</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><label>Vehicle</label><select name="vehicle_id"><option value="">All vehicles</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((int)request('vehicle_id')===$vehicle->id)>{{ $vehicle->registration_no }} — {{ $vehicle->name }}</option>@endforeach</select></div>
    <div><label>Action</label><select name="action"><option value="">All actions</option>@foreach(\App\Models\VehicleMaintenanceLog::ACTIONS as $key=>$label)<option value="{{ $key }}" @selected(request('action')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="full actions"><button class="btn">Apply Filters</button><a class="btn light" href="{{ route('fleet.reports.maintenance') }}">Reset</a></div>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Maintenance Cost</span><strong>{{ number_format((float)$maintenanceTotal,2) }}</strong></div>
    <div class="card stat"><span class="muted">Maintenance Logs</span><strong>{{ $maintenanceCount }}</strong></div>
</div>

<section class="card">@include('partials.per_page')<table><thead><tr><th>Date</th><th>Vehicle</th><th>Item</th><th>Action</th><th>Mileage</th><th>Cost</th><th>Vendor</th><th>Details</th><th>Photos / Video</th><th>Entered By</th></tr></thead><tbody>
@forelse($maintenanceLogs as $log)<tr role="link" tabindex="0" title="Click to view maintenance details" style="cursor:pointer" onclick="if (!event.target.closest('a,button')) window.location='{{ route('fleet.maintenance-logs.show', $log) }}'" onkeydown="if (event.key === 'Enter') window.location='{{ route('fleet.maintenance-logs.show', $log) }}'"><td>{{ $log->service_date->format('Y-m-d') }}</td><td><a href="{{ route('fleet.show',$log->vehicle) }}">{{ $log->vehicle->registration_no }}</a></td><td><a href="{{ route('fleet.maintenance-logs.show', $log) }}">{{ $log->item?->name ?? $log->work_name ?? 'General' }}</a></td><td>{{ \App\Models\VehicleMaintenanceLog::ACTIONS[$log->action] ?? ucfirst($log->action) }}</td><td>{{ $log->mileage ? number_format($log->mileage) : 'N/A' }}</td><td>{{ number_format((float)$log->cost,2) }}</td><td>{{ $log->vendor ?? 'N/A' }}</td><td>{{ $log->details ?? 'N/A' }}</td><td><div class="actions">@foreach($log->photos as $index => $photo)<a href="{{ route('fleet.maintenance.photos.show', $photo) }}" target="_blank" rel="noopener">Photo {{ $index + 1 }}</a>@endforeach @if($log->youtube_url)<a href="{{ $log->youtube_url }}" target="_blank" rel="noopener">YouTube Video</a>@endif @if($log->photos->isEmpty() && !$log->youtube_url) N/A @endif</div></td><td>{{ $log->creator?->name ?? 'N/A' }}</td></tr>@empty<tr><td colspan="10">No maintenance logs found.</td></tr>@endforelse</tbody></table><div style="margin-top:14px">{{ $maintenanceLogs->links() }}</div></section>
@endsection
