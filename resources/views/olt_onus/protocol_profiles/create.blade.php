@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Add OLT Protocol/Profile</h1>
        <div class="muted">Create a reusable brand/protocol polling profile.</div>
    </div>
    <a class="btn light" href="{{ route('olt-onus.protocol-profiles.index') }}">Back</a>
</div>

@include('olt_onus.protocol_profiles.partials.form', [
    'action' => route('olt-onus.protocol-profiles.store'),
    'method' => 'post',
    'submitLabel' => 'Save Protocol',
])
@endsection
