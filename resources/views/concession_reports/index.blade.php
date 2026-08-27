@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($n) => '৳ '.number_format((float) $n, 2);
@endphp

<div class="topbar">
    <div>
        <h1>Concession Actions</h1>
        <div class="muted">Every grace period, validity override, quick-activate and force action &mdash; with the package cost it would have earned.</div>
    </div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('concession-reports.summary', request()->query()) }}">Cost by Admin &rarr;</a>
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
    @if ($filters['customer_id'])
        <input type="hidden" name="customer_id" value="{{ $filters['customer_id'] }}">
    @endif
    <div class="actions">
        <button class="btn secondary" type="submit">Filter</button>
        <a class="btn light" href="{{ route('concession-reports.index') }}">Reset</a>
    </div>
</form>

<div class="grid stats" style="margin-bottom:16px">
    <div class="card stat"><span class="muted">Actions</span><strong>{{ number_format($totals['count']) }}</strong></div>
    <div class="card stat"><span class="muted">Total give-away value</span><strong>{{ $fmt($totals['value']) }}</strong></div>
    <div class="card stat"><span class="muted">Open (still running)</span><strong>{{ number_format($totals['pending']) }}</strong></div>
</div>

@include('partials.per_page')

<div class="table-scroll" data-pagination-summary>
<table>
    <thead>
        <tr>
            <th>SL</th>
            <th>When</th>
            <th>Admin</th>
            <th>Party</th>
            <th>Action</th>
            <th>Reason</th>
            <th>Free days</th>
            <th>Validity change</th>
            <th>Package</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($logs as $log)
            <tr>
                <td>{{ $loop->iteration + ($logs->firstItem() ? $logs->firstItem() - 1 : 0) }}</td>
                <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $log->user_name ?: 'System' }}</td>
                <td>
                    {{ $log->customer?->name ?? 'Deleted party' }}
                    @if ($log->customer?->connection_id)
                        <span class="muted">&middot; {{ $log->customer->connection_id }}</span>
                    @endif
                </td>
                <td>{{ $log->actionLabel() }}</td>
                <td class="muted" style="max-width:280px">{{ $log->reason }}</td>
                <td>{{ $log->free_days !== null ? $log->free_days : '—' }}</td>
                <td>
                    @if ($log->new_valid_until)
                        {{ $log->previous_valid_until?->format('d/m/Y') ?? 'not set' }} &rarr; {{ $log->new_valid_until->format('d/m/Y') }}
                    @else
                        —
                    @endif
                </td>
                <td>{{ $log->package?->name ?? '—' }}</td>
                <td>
                    {{ $fmt($log->estimated_value) }}
                    @if ($log->value_status === 'pending')
                        <span class="muted">(running)</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="10" class="muted">No concession actions recorded for this filter.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:16px">{{ $logs->links() }}</div>
@endsection
