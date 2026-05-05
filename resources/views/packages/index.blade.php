@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Packages</h1><div class="muted">Internet plans and monthly prices</div></div>
    <a class="btn" href="{{ route('packages.create') }}">Add Package</a>
</div>

<table>
    <thead><tr><th>Name</th><th>Speed</th><th>Monthly Price</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($packages as $package)
        <tr data-href="{{ route('packages.show', $package) }}">
            <td>{{ $package->name }}</td>
            <td>{{ $package->speed }}</td>
            <td>{{ number_format($package->monthly_price, 2) }}</td>
            <td><span class="badge {{ $package->status }}">{{ $package->status }}</span></td>
        </tr>
    @empty
        <tr><td colspan="4">No packages found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $packages->links() }}</div>
@endsection
