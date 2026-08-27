@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($n) => '৳ '.number_format((float) $n, 2);
@endphp

<div class="topbar">
    <div>
        <h1>Concession Cost by Admin</h1>
        <div class="muted">How much package revenue each admin has given away, split by option.</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('concession-reports.index', request()->query()) }}">&larr; Action List</a>
    </div>
</div>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <select name="action_type">
        <option value="">All actions</option>
        @foreach ($actionOptions as $key => $label)
            <option value="{{ $key }}" @selected($filters['action_type'] === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="user_id">
        <option value="">All admins</option>
        @foreach ($adminOptions as $admin)
            <option value="{{ $admin->id }}" @selected((int) $filters['user_id'] === (int) $admin->id)>{{ $admin->name }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ $filters['from'] }}">
    <input type="date" name="to" value="{{ $filters['to'] }}">
    <div class="actions">
        <button class="btn secondary" type="submit">Filter</button>
        <a class="btn light" href="{{ route('concession-reports.summary') }}">Reset</a>
    </div>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Admins</span><strong>{{ number_format($byAdmin->count()) }}</strong></div>
    <div class="card stat"><span class="muted">Total actions</span><strong>{{ number_format($grandCount) }}</strong></div>
    <div class="card stat"><span class="muted">Total give-away value</span><strong>{{ $fmt($grandTotal) }}</strong></div>
</div>

@forelse ($byAdmin as $admin)
    <div class="card" style="margin-bottom:14px">
        <div class="section-head">
            <div>
                <h2 style="margin-bottom:2px">{{ $admin['admin'] }}</h2>
                <div class="muted">{{ number_format($admin['total_count']) }} action(s) &middot; {{ $fmt($admin['total_value']) }} total</div>
            </div>
        </div>
        <table>
            <thead><tr><th>Option</th><th>Count</th><th>Give-away value</th></tr></thead>
            <tbody>
                @foreach ($admin['actions'] as $action)
                    <tr>
                        <td>{{ $action['label'] }}</td>
                        <td>{{ number_format($action['count']) }}</td>
                        <td>{{ $fmt($action['value']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th>{{ number_format($admin['total_count']) }}</th>
                    <th>{{ $fmt($admin['total_value']) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
@empty
    <div class="card muted">No concession actions recorded for this filter.</div>
@endforelse
@endsection
