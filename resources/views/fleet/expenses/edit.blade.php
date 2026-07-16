@extends('layouts.app')

@section('content')
<div class="topbar"><div><h1>Edit Expense Draft</h1><div class="muted">{{ $expense->vehicle->registration_no }} — changes will be saved in Edit History</div></div><a class="btn light" href="{{ route('fleet.expenses.show',$expense) }}">Cancel</a></div>
<div style="padding:13px 15px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;margin-bottom:16px"><strong>Draft expense:</strong> Edit carefully, then use Final & Lock from the detail page when complete.</div>
<form method="post" action="{{ route('fleet.expenses.update',$expense) }}" class="card form-grid">@csrf @method('put')
    <div><label>Category</label><select name="category">@foreach(\App\Models\VehicleExpense::CATEGORIES as $key=>$label)<option value="{{ $key }}" @selected(old('category',$expense->category)===$key)>{{ $label }}</option>@endforeach</select></div>
    <div><label>Date</label><input type="date" name="expense_date" value="{{ old('expense_date',$expense->expense_date->format('Y-m-d')) }}" required></div>
    <div><label>Driver / Employee</label><select name="employee_id"><option value="">Not selected</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((int)old('employee_id',$expense->employee_id)===$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
    <div><label>Amount</label><input type="number" min="0.01" step="0.01" name="amount" value="{{ old('amount',$expense->amount) }}" required></div>
    <div><label>Quantity</label><input type="number" min="0.001" step="0.001" name="quantity" value="{{ old('quantity',$expense->quantity) }}"></div>
    <div><label>Unit</label><input name="unit" value="{{ old('unit',$expense->unit) }}"></div>
    <div><label>Mileage</label><input type="number" min="0" name="mileage" value="{{ old('mileage',$expense->mileage) }}"></div>
    <div><label>Trip Reference</label><input name="trip_reference" value="{{ old('trip_reference',$expense->trip_reference) }}"></div>
    <div><label>Vendor</label><input name="vendor" value="{{ old('vendor',$expense->vendor) }}"></div>
    <div class="full"><label>Description</label><textarea name="description" rows="4">{{ old('description',$expense->description) }}</textarea></div>
    <div class="full actions"><button class="btn">Save Draft Changes</button><a class="btn light" href="{{ route('fleet.expenses.show',$expense) }}">Cancel</a></div>
</form>
@endsection
