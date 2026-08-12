@extends('layouts.app')

@section('content')
<div class="customer-shell">
    <section class="topbar">
        <div>
            <h1>{{ $customer->name }} <span class="muted">- Edit History</span></h1>
            <div class="muted">
                {{ $customer->connection_id ?: 'ID not assigned' }} &bull; {{ $customer->phone ?: 'Phone not provided' }}
            </div>
            @if ($customer->activeSubscription?->package)
                <div class="muted" style="margin-top:4px;">
                    Package: {{ $customer->activeSubscription->package->name }}
                </div>
            @endif
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('customers.show', $customer) }}">Back to Profile</a>
        </div>
    </section>

    <section class="card" style="margin-bottom: 14px;">
        <h2>Customer Change Log</h2>
        <p style="margin-bottom:0;">Every edit made to this profile is recorded here.</p>
    </section>

    @include('partials.record_versions', ['versions' => $versions])
</div>
@endsection
