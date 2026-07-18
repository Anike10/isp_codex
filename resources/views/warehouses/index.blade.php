@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Warehouses</h1><div class="muted">Warehouse-wise stock and transfer control</div></div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('warehouse-transfers.create') }}">Transfer Stock</a>
        <a class="btn light" href="{{ route('warehouse-movements.index') }}">Movement History</a>
    </div>
</div>

<form method="post" action="{{ route('warehouses.store') }}" class="card form-grid" style="margin-bottom:16px">
    @csrf
    <div><label>Warehouse Name</label><input name="name" value="{{ old('name') }}" required></div>
    <div><label>Code</label><input name="code" value="{{ old('code') }}" placeholder="e.g. DHAKA" required></div>
    <div><label>Address</label><input name="address" value="{{ old('address') }}"></div>
    <div class="actions" style="align-items:end"><button class="btn" type="submit">Add Warehouse</button></div>
</form>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div><label>Search</label><input name="search" value="{{ request('search') }}" placeholder="Warehouse name, code, or address"></div>
    <div><label>Status</label><select name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <div class="actions" style="align-items:end"><button class="btn secondary" type="submit">Search</button><a class="btn light" href="{{ route('warehouses.index') }}">Reset</a></div>
</form>

<table>
    <thead><tr><th>Warehouse</th><th>Code</th><th>Address</th><th>Products in Stock</th><th>Total Units</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    @foreach ($warehouses as $warehouse)
        <tr data-href="{{ route('warehouses.show', $warehouse) }}">
            <td><a href="{{ route('warehouses.show', $warehouse) }}">{{ $warehouse->name }}</a> @if($warehouse->is_default)<span class="badge">Default</span>@endif</td>
            <td>{{ $warehouse->code }}</td>
            <td>{{ $warehouse->address ?: 'N/A' }}</td>
            <td>{{ $warehouse->stocked_products_count }}</td>
            <td>{{ (int) $warehouse->stocks_sum_quantity }}</td>
            <td>{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</td>
            <td><a class="btn light" href="{{ route('warehouses.show', $warehouse) }}">View Stock</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
