@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Edit Warehouse</h1><div class="muted">Update warehouse details, status, or default selection</div></div>
    <a class="btn light" href="{{ route('warehouses.index') }}">Back</a>
</div>

<form method="post" action="{{ route('warehouses.update', $warehouse) }}" class="card form-grid">
    @csrf
    @method('PUT')
    <div><label>Warehouse Name</label><input name="name" value="{{ old('name', $warehouse->name) }}" required></div>
    <div><label>Code</label><input name="code" value="{{ old('code', $warehouse->code) }}" required></div>
    <div><label>Address</label><input name="address" value="{{ old('address', $warehouse->address) }}"></div>
    <div><label>Status</label><select name="is_active" required><option value="1" @selected(old('is_active', $warehouse->is_active) == 1)>Active</option><option value="0" @selected(old('is_active', $warehouse->is_active) == 0)>Inactive</option></select></div>
    <div class="full"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_default" value="1" style="width:auto" @checked(old('is_default', $warehouse->is_default))> Default warehouse</label><span class="muted">The default warehouse is always active and cannot be deleted.</span></div>
    <div class="full"><button class="btn" type="submit">Update Warehouse</button></div>
</form>
@endsection
