@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Edit OLT Protocol/Profile</h1>
        <div class="muted">Update brand/profile polling defaults.</div>
        <div class="muted" style="margin-top:6px">Use this after changing EPON/GPON profile so incompatible polling commands are replaced.</div>
    </div>
    <a class="btn light" href="{{ route('olt-onus.protocol-profiles.index') }}">Back</a>
</div>

@include('olt_onus.protocol_profiles.partials.form', [
    'action' => route('olt-onus.protocol-profiles.update', $profile),
    'method' => 'put',
    'submitLabel' => 'Update Protocol',
])
@endsection
