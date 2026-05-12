@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Add Employee</h1>
        <div class="muted">Set salary, effective date, and yearly bonus policy</div>
    </div>
    <a class="btn light" href="{{ route('employees.index') }}">Back</a>
</div>

<form method="post" action="{{ route('employees.store') }}" class="card form-grid">
    @csrf
    <div><label>Name</label><input name="name" value="{{ old('name') }}" required></div>
    <div><label>Designation</label><input name="designation" value="{{ old('designation') }}" placeholder="Technician, Manager"></div>
    <div><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>
    <div><label>Join Date</label><input type="date" name="join_date" value="{{ old('join_date') }}"></div>
    <div><label>Current Salary</label><input type="number" step="0.01" min="0" name="current_salary" value="{{ old('current_salary', 0) }}" required></div>
    <div><label>Salary Effective From</label><input type="date" name="salary_effective_from" value="{{ old('salary_effective_from', now()->toDateString()) }}"></div>
    <div><label>Yearly Bonus Count</label><input type="number" min="0" max="12" name="yearly_bonus_count" value="{{ old('yearly_bonus_count', 2) }}" required></div>
    <div><label>Bonus Percent</label><input type="number" step="0.01" min="0" name="bonus_percent" value="{{ old('bonus_percent', 50) }}" required></div>
    <div>
        <label>Status</label>
        <select name="status" required>
            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="full"><label>Note</label><textarea name="note">{{ old('note') }}</textarea></div>
    <div class="full"><button class="btn" type="submit">Save Employee</button></div>
</form>
@endsection
