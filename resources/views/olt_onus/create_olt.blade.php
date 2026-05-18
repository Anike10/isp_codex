@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Add OLT</h1>
        <div class="muted">Configure read-only SSH/Telnet access and live ONU show commands.</div>
    </div>
    <a class="btn light" href="{{ route('olt-onus.index') }}">Back</a>
</div>

@include('olt_onus.partials.olt_form', [
    'action' => route('olt-onus.olts.store'),
    'method' => 'post',
    'submitLabel' => 'Save OLT',
])
@endsection
