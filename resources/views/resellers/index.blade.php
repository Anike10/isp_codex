@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Resellers</h1><div class="muted">Prepaid wallets, daily limits, assigned customers, and outstanding bills</div></div>
    @if (auth()->user()?->hasPermission('manage_customers'))
        <a class="btn" href="{{ route('customers.create') }}">Add Reseller Party</a>
    @endif
</div>

<form method="get" class="card form-grid" style="margin-bottom:16px">
    <div><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Name, phone, or email"></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div class="full actions"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('resellers.index') }}">Reset</a></div>
</form>

@include('partials.per_page')

<table>
    <thead><tr><th>#</th><th>Reseller</th><th>Wallet</th><th>Daily Limit</th><th>Customers</th><th>Customer Due</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($resellers as $reseller)
        <tr data-href="{{ route('resellers.show', $reseller) }}">
            <td>{{ $resellers->firstItem() + $loop->index }}</td>
            <td><strong>{{ $reseller->name }}</strong><div class="muted">{{ $reseller->phone }}{{ $reseller->email ? ' · '.$reseller->email : '' }}</div></td>
            <td><strong>{{ number_format((float) $reseller->account_balance, 2) }}</strong></td>
            <td>{{ $reseller->reseller_daily_payment_limit !== null ? number_format((float) $reseller->reseller_daily_payment_limit, 2) : 'Unlimited' }}</td>
            <td>{{ $reseller->reseller_customers_count }}</td>
            <td>{{ number_format((float) ($reseller->assigned_due_amount ?? 0), 2) }}</td>
            <td><span class="badge {{ $reseller->status }}">{{ ucfirst($reseller->status) }}</span></td>
            <td class="actions"><a class="btn secondary" href="{{ route('resellers.show', $reseller) }}">Dashboard</a>@if(auth()->user()?->hasPermission('manage_customers'))<a class="btn light" href="{{ route('customers.edit', $reseller) }}">Edit</a>@endif</td>
        </tr>
    @empty
        <tr><td colspan="8">No resellers found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $resellers->links() }}</div>
@endsection
