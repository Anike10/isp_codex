@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Fleet Settings</h1><div class="muted">Control maintenance work photo uploads</div></div>
    <a class="btn light" href="{{ route('fleet.index') }}">Vehicles</a>
</div>

<section class="card" style="max-width:720px">
    <h2>Maintenance Work Photos</h2>
    <form method="post" action="{{ route('fleet.settings.update') }}" class="form-grid">
        @csrf
        <div>
            <label>Maximum Size Per Image (MB)</label>
            <input type="number" name="image_max_mb" min="1" max="50" value="{{ old('image_max_mb', $imageMaxMb) }}" required>
            <div class="muted" style="margin-top:6px">Applies to each JPG, PNG, or WebP image. Up to {{ \App\Services\FleetMaintenanceMediaService::MAX_PHOTO_COUNT }} images can be added to one work entry.</div>
        </div>
        <div class="full"><button class="btn">Save Fleet Settings</button></div>
    </form>
</section>
@endsection
