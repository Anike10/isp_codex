@extends('layouts.app')

@section('content')
@php
    $isEdit = isset($package);
@endphp
<div class="topbar">
    <div><h1>{{ $isEdit ? 'Edit Package' : 'Add Package' }}</h1><div class="muted">{{ $isEdit ? 'Update monthly internet plan details' : 'Create a monthly internet plan' }}</div></div>
    <div class="actions">
        @if ($isEdit)
            <a class="btn light" href="{{ route('packages.show', $package) }}">Back</a>
        @else
            <a class="btn light" href="{{ route('packages.index') }}">Back</a>
        @endif
    </div>
</div>

<form method="post" action="{{ $isEdit ? route('packages.update', $package) : route('packages.store') }}" class="card form-grid">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif
    <div><label>Name</label><input name="name" value="{{ old('name', $package->name ?? '') }}" required></div>
    <div><label>Speed</label><input name="speed" value="{{ old('speed', $package->speed ?? '') }}" placeholder="20 Mbps" required></div>
    <div><label>MikroTik Profile</label><input name="mikrotik_profile" value="{{ old('mikrotik_profile', $package->mikrotik_profile ?? '') }}" placeholder="Same as RouterOS PPP profile"></div>
    <div><label>Monthly Price</label><input type="number" step="0.01" name="monthly_price" value="{{ old('monthly_price', $package->monthly_price ?? '') }}" required></div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', $package->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $package->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="full"><label>Description</label><textarea name="description">{{ old('description', $package->description ?? '') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">{{ $isEdit ? 'Update Package' : 'Save Package' }}</button></div>
</form>
@endsection
