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
    <div>
        <label>Default IP Pool</label>
        <select name="default_ip_pool">
            <option value="">RouterOS default / কোনো App default নেই</option>
            @foreach ($ipPoolNames as $poolName)
                <option value="{{ $poolName }}" @selected(old('default_ip_pool', $package->default_ip_pool ?? '') === $poolName)>{{ $poolName }}</option>
            @endforeach
        </select>
        <div class="muted">Package profile MikroTik-এ export/sync হলে এই pool remote-address হিসেবে ব্যবহৃত হবে।</div>
    </div>
    <div><label>Monthly Price</label><input type="number" step="0.01" name="monthly_price" value="{{ old('monthly_price', $package->monthly_price ?? '') }}" required></div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', $package->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $package->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="full"><label>Description</label><textarea name="description">{{ old('description', $package->description ?? '') }}</textarea></div>
    @if ($isEdit)
        <div class="full card" style="margin:0">
            <h3 style="margin-top:0">বর্তমানে RouterOS profile-এ চলমান IP Pool</h3>
            @forelse ($runningPoolProfiles as $runningProfile)
                <div style="display:flex;justify-content:space-between;gap:16px;padding:7px 0;border-bottom:1px solid var(--line)">
                    <span>{{ $runningProfile->router?->name ?? 'Unknown router' }}</span>
                    <span>
                        <strong>{{ $runningProfile->remote_address ?: 'RouterOS default / none' }}</strong>
                        @if ($package->default_ip_pool && $runningProfile->remote_address === $package->default_ip_pool)
                            <span class="badge active">Selected default</span>
                        @endif
                        <span class="muted" style="display:block">Last imported: {{ $runningProfile->imported_at?->format('Y-m-d H:i:s') ?? 'Unknown' }}</span>
                    </span>
                </div>
            @empty
                <div class="muted">এই package profile-এর কোনো imported RouterOS snapshot নেই। Router থেকে Profiles import করলে চলমান pool এখানে দেখা যাবে।</div>
            @endforelse
        </div>
    @endif
    <div class="full"><button class="btn" type="submit">{{ $isEdit ? 'Update Package' : 'Save Package' }}</button></div>
</form>
@endsection
