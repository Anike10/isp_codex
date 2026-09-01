@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $customer->name }} &mdash; Data Usage</h1>
        <div class="muted">Download &amp; upload from PPP disconnect logs, {{ $view === 'day' ? 'grouped by day' : 'one row per session' }}.</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn light" href="{{ route('troubleshoot.data-usage.select', ['view' => $view]) }}">Pick another party</a>
        <a class="btn light" href="{{ route('troubleshoot.data-usage') }}">&larr; All parties</a>
    </div>
</div>

<div class="card" style="display:flex;gap:26px;flex-wrap:wrap">
    <div><span class="muted" style="display:block;font-size:12px">Sessions</span><strong>{{ (int) ($totals->sessions ?? 0) }}</strong></div>
    <div><span class="muted" style="display:block;font-size:12px">Download</span><strong>@include('troubleshoot._bytes', ['bytes' => $totals->download_bytes ?? 0])</strong></div>
    <div><span class="muted" style="display:block;font-size:12px">Upload</span><strong>@include('troubleshoot._bytes', ['bytes' => $totals->upload_bytes ?? 0])</strong></div>
    <div><span class="muted" style="display:block;font-size:12px">Total</span><strong>@include('troubleshoot._bytes', ['bytes' => $totals->total_bytes ?? 0])</strong></div>
</div>

<form method="get" class="card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
    <div>
        <label class="muted" style="display:block;font-size:12px">View</label>
        <select name="view">
            <option value="day" @selected($view === 'day')>By day</option>
            <option value="session" @selected($view === 'session')>By session</option>
        </select>
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">Window</label>
        <select name="days" @disabled($rangeActive)>
            @foreach ([1 => 'Last 24 hours', 7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last year'] as $val => $lbl)
                <option value="{{ $val }}" @selected(! $rangeActive && $days === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">From date</label>
        <input type="date" name="from" value="{{ $from?->toDateString() }}">
    </div>
    <div>
        <label class="muted" style="display:block;font-size:12px">To date</label>
        <input type="date" name="to" value="{{ $to?->toDateString() }}">
    </div>
    <button class="btn" type="submit">Apply</button>
    <a class="btn light" href="{{ route('troubleshoot.data-usage.show', $customer) }}">Reset</a>
    <span class="muted" style="margin-left:auto">
        @if ($rangeActive)
            {{ $since->format('d/m/Y') }} &ndash; {{ $until->format('d/m/Y') }}
        @else
            since {{ $since->format('d/m/Y H:i') }}
        @endif
    </span>
    <span class="muted" style="flex-basis:100%;font-size:12px">Set a From/To date to report on an exact range; leave both empty to use the rolling window.</span>
</form>

@include('troubleshoot._retention')

<div class="table-scroll">
    <table>
        @if ($view === 'day')
            <thead>
                <tr>
                    <th>Day</th>
                    <th class="col-center">Sessions</th>
                    <th class="col-center">Download</th>
                    <th class="col-center">Upload</th>
                    <th class="col-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><strong>{{ \Illuminate\Support\Carbon::parse($row->day)->format('d/m/Y') }}</strong></td>
                        <td class="col-center">{{ (int) $row->sessions }}</td>
                        <td class="col-center">@include('troubleshoot._bytes', ['bytes' => $row->download_bytes])</td>
                        <td class="col-center">@include('troubleshoot._bytes', ['bytes' => $row->upload_bytes])</td>
                        <td class="col-center"><strong>@include('troubleshoot._bytes', ['bytes' => $row->total_bytes])</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No usage logged in this window.</td></tr>
                @endforelse
            </tbody>
        @else
            <thead>
                <tr>
                    <th>Disconnected at</th>
                    <th>Router</th>
                    <th>Uptime</th>
                    <th class="col-center">Download</th>
                    <th class="col-center">Upload</th>
                    <th class="col-center">Total</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->disconnected_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                        <td>{{ $row->router?->name ?? '—' }}</td>
                        <td>{{ $row->uptime ?? '—' }}</td>
                        <td class="col-center">@include('troubleshoot._bytes', ['bytes' => $row->download_bytes])</td>
                        <td class="col-center">@include('troubleshoot._bytes', ['bytes' => $row->upload_bytes])</td>
                        <td class="col-center"><strong>@include('troubleshoot._bytes', ['bytes' => $row->download_bytes + $row->upload_bytes])</strong></td>
                        <td>{{ $row->disconnect_reason ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No sessions logged in this window.</td></tr>
                @endforelse
            </tbody>
        @endif
    </table>
</div>

<div style="margin-top:14px">{{ $rows->links() }}</div>
@endsection
