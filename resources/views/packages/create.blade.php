@extends('layouts.app')

@section('content')
<div class="topbar">
    <div><h1>Add Package</h1><div class="muted">Create a monthly internet plan</div></div>
    <a class="btn light" href="{{ route('packages.index') }}">Back</a>
</div>

<form method="post" action="{{ route('packages.store') }}" class="card form-grid">
    @csrf
    <div><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
    <div><label>Speed</label><input name="speed" value="{{ old('speed') }}" placeholder="20 Mbps" required></div>
    <div><label>Monthly Price</label><input type="number" step="0.01" name="monthly_price" value="{{ old('monthly_price') }}" required></div>
    <div>
        <label>Status</label>
        <select name="status" required><option value="active">Active</option><option value="inactive">Inactive</option></select>
    </div>
    <div class="full"><label>Description</label><textarea name="description">{{ old('description') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Save Package</button></div>
</form>
@endsection
