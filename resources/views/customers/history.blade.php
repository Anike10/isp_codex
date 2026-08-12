@extends('layouts.app')

@section('content')
@php
    $isDeletedCustomer = method_exists($customer, 'trashed') && $customer->trashed();
@endphp
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
            @if ($isDeletedCustomer)
                <a class="btn light" href="{{ route('customers.deleted') }}">Back to Deleted Parties</a>
            @else
                <a class="btn" href="{{ route('customers.show', $customer) }}">Back to Profile</a>
            @endif
        </div>
    </section>

    <section class="card" style="margin-bottom: 14px;">
        <h2>Customer Change Log</h2>
        <p style="margin-bottom:0;">Every edit made to this profile is recorded here.</p>
    </section>

    @include('partials.record_versions', ['versions' => $versions])
</div>
@endsection
