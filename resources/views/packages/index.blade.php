@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Packages</h1><div class="muted">Internet plans and monthly prices</div></div>
    <a class="btn" href="{{ route('packages.create') }}">Add Package</a>
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div class="full"><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Package name, speed, MikroTik profile, description"></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div><label>Min Price</label><input type="number" step="0.01" name="min_price" value="{{ request('min_price') }}"></div>
    <div><label>Max Price</label><input type="number" step="0.01" name="max_price" value="{{ request('max_price') }}"></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('packages.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Name</th><th>Speed</th><th>MikroTik Profile</th><th>Monthly Price</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($packages as $package)
        <tr data-href="{{ route('packages.show', $package) }}">
            <td>{{ $package->name }}</td>
            <td>{{ $package->speed }}</td>
            <td>{{ $package->mikrotik_profile }}</td>
            <td>{{ number_format($package->monthly_price, 2) }}</td>
            <td><span class="badge {{ $package->status }}">{{ $package->status }}</span></td>
        </tr>
    @empty
        <tr><td colspan="5">No packages found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $packages->links() }}</div>
@endsection
