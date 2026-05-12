@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Edit Employee</h1>
        <div class="muted">{{ $employee->name }}</div>
    </div>
    <a class="btn light" href="{{ route('employees.show', $employee) }}">Back</a>
</div>

<form method="post" action="{{ route('employees.update', $employee) }}" class="card form-grid">
    @csrf
    @method('PUT')
    <div><label>Name</label><input name="name" value="{{ old('name', $employee->name) }}" required></div>
    <div><label>Designation</label><input name="designation" value="{{ old('designation', $employee->designation) }}"></div>
    <div><label>Phone</label><input name="phone" value="{{ old('phone', $employee->phone) }}"></div>
    <div><label>Join Date</label><input type="date" name="join_date" value="{{ old('join_date', $employee->join_date?->format('Y-m-d')) }}"></div>
    <div><label>Yearly Bonus Count</label><input type="number" min="0" max="12" name="yearly_bonus_count" value="{{ old('yearly_bonus_count', $employee->yearly_bonus_count) }}" required></div>
    <div><label>Bonus Percent</label><input type="number" step="0.01" min="0" name="bonus_percent" value="{{ old('bonus_percent', $employee->bonus_percent) }}" required></div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', $employee->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $employee->status) === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="full"><label>Note</label><textarea name="note">{{ old('note', $employee->note) }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Update Employee</button></div>
</form>
@endsection
