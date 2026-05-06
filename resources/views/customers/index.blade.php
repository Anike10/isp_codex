@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Customers</h1><div class="muted">Internet connection and customer profiles</div></div>
    <a class="btn" href="{{ route('customers.create') }}">Add Customer</a>
</div>

<form method="get" class="card actions" style="margin-bottom:16px">
    <input name="search" value="{{ request('search') }}" placeholder="Search by name, phone, connection ID">
    <button class="btn secondary" type="submit">Search</button>
    <a class="btn light" href="{{ route('customers.index') }}">Reset</a>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>Name</th><th>Phone</th><th>Connection</th><th>Package</th><th>Balance</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($customers as $customer)
        <tr data-href="{{ route('customers.show', $customer) }}">
            <td>{{ $customer->name }}</td>
            <td>{{ $customer->phone }}</td>
            <td>{{ $customer->connection_id }}</td>
            <td>{{ $customer->activeSubscription?->package?->name ?? 'No package' }}</td>
            <td>{{ number_format($customer->account_balance, 2) }}</td>
            <td><span class="badge {{ $customer->status }}">{{ $customer->status }}</span></td>
            <td class="actions">
                <a class="btn light" href="{{ route('customers.show', $customer) }}">View</a>
                <a class="btn light" href="{{ route('customers.edit', $customer) }}">Edit</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="7">No customers found.</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $customers->links() }}</div>
@endsection
