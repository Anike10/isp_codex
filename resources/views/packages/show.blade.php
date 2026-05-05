@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $package->name }}</h1>
        <div class="muted">{{ $package->speed }} internet package</div>
    </div>
    <a class="btn light" href="{{ route('packages.index') }}">Back</a>
</div>

<div class="grid two">
    <section class="card">
        <h2>Package Details</h2>
        <p><strong>Name:</strong> {{ $package->name }}</p>
        <p><strong>Speed:</strong> {{ $package->speed }}</p>
        <p><strong>Monthly Price:</strong> {{ number_format($package->monthly_price, 2) }}</p>
        <p><strong>Status:</strong> <span class="badge {{ $package->status }}">{{ $package->status }}</span></p>
    </section>
    <section class="card">
        <h2>Usage</h2>
        <p><strong>Total Subscriptions:</strong> {{ $package->subscriptions_count }}</p>
        <p><strong>Description:</strong> {{ $package->description ?? 'No description' }}</p>
    </section>
</div>
@endsection
