@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>OLT Protocol/Profile</h1>
        <div class="muted">Brand/profile defaults used by OLT polling.</div>
    </div>
    <div class="actions">
        <a class="btn light" href="{{ route('olt-onus.index') }}">OLT ONUs</a>
        <a class="btn" href="{{ route('olt-onus.protocol-profiles.create') }}">Add Protocol/Profile</a>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Key</th>
            <th>Label</th>
            <th>Brand</th>
            <th>PON Interface</th>
            <th>VLAN/MAC</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($profiles as $profile)
            <tr>
                <td><strong>{{ $profile->key }}</strong></td>
                <td>{{ $profile->label }}</td>
                <td>{{ $profile->brand ?: 'N/A' }}</td>
                <td>{{ $profile->pon_interface_command }}</td>
                <td>
                    <span class="badge {{ $profile->supports_vlan_polling ? 'active' : 'inactive' }}">VLAN</span>
                    <span class="badge {{ $profile->supports_mac_polling ? 'active' : 'inactive' }}">MAC</span>
                </td>
                <td><a class="btn light" href="{{ route('olt-onus.protocol-profiles.edit', $profile) }}">Edit</a></td>
            </tr>
        @empty
            <tr><td colspan="6">No protocol profiles found.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
