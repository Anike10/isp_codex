@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; Data Usage by Party</h1>
        <div class="muted">Pick a party, choose how to break it down and over what period, then open its report.</div>
    </div>
    <a class="btn light" href="{{ route('troubleshoot.data-usage') }}">All parties summary</a>
</div>

<style>
    .du-seg { display: inline-flex; border: 1px solid var(--line, #d0d5dd); border-radius: 8px; overflow: hidden; }
    .du-seg label {
        margin: 0; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
        color: #475467; background: #fff; user-select: none; line-height: 1.2;
    }
    .du-seg label + label { border-left: 1px solid var(--line, #d0d5dd); }
    .du-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .du-seg label.is-on,
    .du-seg label:has(input:checked) { background: var(--accent, #1d76c9); color: #fff; }
    .du-seg label:has(input:focus-visible) { outline: 3px solid rgba(29, 118, 201, .25); outline-offset: -3px; }
    .du-filter { display: flex; gap: 14px; align-items: end; flex-wrap: wrap; }
    .du-filter .field { display: flex; flex-direction: column; gap: 4px; }
    .du-filter label.cap { font-size: 12px; color: #667085; }
    .du-filter .grow { flex: 1; min-width: 240px; }
</style>

<form method="get" action="{{ route('troubleshoot.data-usage.select') }}" class="card du-filter">
    <div class="field grow">
        <label class="cap">Party</label>
        <select name="customer" required>
            <option value="">Type a name, connection ID or username&hellip;</option>
            @foreach ($parties as $party)
                <option value="{{ $party['id'] }}" @selected($selected === $party['id'])>{{ $party['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label class="cap">Break down</label>
        <div class="du-seg" role="group" aria-label="Break down by">
            <label class="@if ($view === 'day') is-on @endif">
                <input type="radio" name="view" value="day" @checked($view === 'day')> By day
            </label>
            <label class="@if ($view === 'session') is-on @endif">
                <input type="radio" name="view" value="session" @checked($view === 'session')> By session
            </label>
        </div>
    </div>

    <div class="field">
        <label class="cap">Window</label>
        <select name="days" @disabled($rangeActive)>
            @foreach ([1 => 'Last 24 hours', 7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last year'] as $val => $lbl)
                <option value="{{ $val }}" @selected(! $rangeActive && $days === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label class="cap">From date</label>
        <input type="date" name="from" value="{{ $from?->toDateString() }}">
    </div>
    <div class="field">
        <label class="cap">To date</label>
        <input type="date" name="to" value="{{ $to?->toDateString() }}">
    </div>

    <button class="btn" type="submit">Open</button>
    <button class="btn light" type="submit" name="make_default" value="1" formnovalidate
        title="Remember this view &amp; window as the default for the party report">Make default</button>
    <a class="btn light" href="{{ route('troubleshoot.data-usage.select') }}">Reset</a>

    <span class="muted" style="flex-basis:100%;font-size:12px">
        Set a From/To date for an exact range; leave both empty to use the rolling window.
        “Make default” applies to every party report opened from here or the summary table.
    </span>
    @if ($madeDefault)
        <span class="muted" style="flex-basis:100%;color:var(--brand)">Saved &mdash; the party report now opens with this view &amp; window.</span>
    @endif
</form>

@if ($parties->isEmpty())
    <div class="card"><p class="muted" style="margin:0">No parties on record yet.</p></div>
@endif
@endsection
