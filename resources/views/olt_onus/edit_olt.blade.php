@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Edit OLT</h1>
        <div class="muted">Update connection, brand profile, and read-only polling commands.</div>
    </div>
    <a class="btn light" href="{{ route('olt-onus.index') }}">Back</a>
</div>

@include('olt_onus.partials.olt_form', [
    'action' => route('olt-onus.olts.update', $oltDevice),
    'method' => 'put',
    'submitLabel' => 'Update OLT',
])
@endsection
