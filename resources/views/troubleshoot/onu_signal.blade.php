@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; ONU Signal History</h1>
        <div class="muted">
            Stored ONU Rx/Tx optical power, sampled every {{ $intervalHours }}h.
            Showing <strong>{{ $from->format('d/m/Y') }}</strong> to <strong>{{ $to->format('d/m/Y') }}</strong>.
            The green band is −15 to −25 dBm (normal). Samples older than {{ $retentionDays }} day(s) are deleted (retention is set on the OLT / ONU page).
        </div>
    </div>
</div>

<style>
    .onu-allsig__bar {
        position: sticky; top: 0; z-index: 6;
        display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        padding: 10px 12px; margin-bottom: 14px;
        background: var(--card, #fff); border: 1px solid var(--line, #e6ebf2); border-radius: 10px;
        font-size: 13px;
    }
    .onu-signal__show { display: inline-flex; align-items: center; gap: 10px; margin: 0; }
    .onu-signal__show label { display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
    .onu-allsig__bar .per-page-form { margin: 0; }
    .onu-allsig__card {
        margin: 18px 0 0; padding: 16px;
        border: 1px solid var(--line, #dfe5ec); border-radius: 12px;
        background: var(--panel, #fff); box-shadow: 0 5px 16px rgba(15, 23, 42, .05);
    }
    .onu-allsig__heading {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        padding-bottom: 12px; margin-bottom: 12px; border-bottom: 1px solid var(--line, #e6ebf2);
    }
    .onu-allsig__card h2 { margin: 0; font-size: 16px; line-height: 1.45; }
    .onu-allsig__card h2 .onu-allsig__sl { color: #667085; font-weight: 700; margin-right: 6px; }
    .onu-allsig__card h2 .muted { font-weight: 400; font-size: 12px; }
    .onu-allsig__badges { display: inline-flex; align-items: center; gap: 5px; margin-left: 6px; flex-wrap: wrap; }
    .onu-allsig__badges .badge { font-size: 10px; line-height: 1.2; padding: 4px 7px; }
    .onu-allsig__badges .unstable { background: #fdecec; color: #b42318; border: 1px solid #f4bcbc; }
    .onu-allsig__badges .power-warning { background: #fff4e5; color: #9a4b00; border: 1px solid #f6c98b; }
    .onu-allsig__badges .power-danger { background: #fff0f0; color: #b42318; border: 1px solid #efb4b4; }
    .onu-allsig__meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 9px; margin: 0; }
    .onu-allsig__metric {
        min-width: 0; padding: 9px 10px; border: 1px solid #e7ebf0; border-radius: 8px; background: #f8fafc;
        color: #475467; font-size: 12px;
    }
    .onu-allsig__metric-label { display: block; margin-bottom: 4px; color: #667085; font-size: 10px; font-weight: 800; letter-spacing: .035em; text-transform: uppercase; }
    .onu-allsig__metric b { color: #101828; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .onu-allsig__metric small { display: block; margin-top: 3px; color: #9a4b00; }
    .onu-allsig__metric--wide { grid-column: span 2; }
    .onu-allsig__metric--swing { border-color: #efb56d; background: #fff8eb; box-shadow: inset 3px 0 #e68a18; }
    .onu-allsig__metric--online b { color: #067647; }
    .onu-allsig__latest-values { display: inline-flex; gap: 8px; margin-left: 8px; flex-wrap: wrap; }
    .onu-allsig__latest-values span { padding: 2px 6px; border-radius: 999px; background: #e9f2ff; color: #175cd3; font-weight: 700; font-variant-numeric: tabular-nums; }
    .onu-allsig__chart { margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line, #e6ebf2); }
    .onu-ticket__button { width: auto; min-height: 32px; padding: 7px 11px; font-size: 12px; white-space: nowrap; transition: opacity .15s, box-shadow .15s, filter .15s; }
    /* Stable parties: de-emphasise the ticket button so it does not invite noise. */
    .onu-ticket__button--muted { opacity: .4; filter: grayscale(.45); }
    .onu-ticket__button--muted:hover,
    .onu-ticket__button--muted:focus-visible { opacity: 1; filter: none; }
    /* Rows that need attention: pull the eye to the ticket button. */
    .onu-ticket__button--alert {
        background: #b42318; border-color: #b42318; color: #fff; font-weight: 700;
        animation: onu-ticket-glow 1.6s ease-in-out infinite;
    }
    .onu-ticket__button--alert:hover,
    .onu-ticket__button--alert:focus-visible { background: #952015; border-color: #952015; color: #fff; }
    /* Power out of band but link otherwise steady: amber, gentler pulse. */
    .onu-ticket__button--warn {
        background: #b54708; border-color: #b54708; color: #fff; font-weight: 700;
        animation: onu-ticket-glow-warn 2s ease-in-out infinite;
    }
    .onu-ticket__button--warn:hover,
    .onu-ticket__button--warn:focus-visible { background: #973c06; border-color: #973c06; color: #fff; }
    @keyframes onu-ticket-glow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(180, 35, 24, .55); }
        50% { box-shadow: 0 0 0 7px rgba(180, 35, 24, 0); }
    }
    @keyframes onu-ticket-glow-warn {
        0%, 100% { box-shadow: 0 0 0 0 rgba(181, 71, 8, .5); }
        50% { box-shadow: 0 0 0 6px rgba(181, 71, 8, 0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .onu-ticket__button--alert { animation: none; box-shadow: 0 0 0 3px rgba(180, 35, 24, .35); }
        .onu-ticket__button--warn { animation: none; box-shadow: 0 0 0 3px rgba(181, 71, 8, .32); }
    }
    @media (max-width: 620px) {
        .onu-allsig__card { padding: 13px; }
        .onu-allsig__metric--wide { grid-column: span 1; }
        .onu-ticket__button { width: 100%; justify-content: center; }
    }
</style>

<form method="get" class="card filter-form" style="margin-bottom:16px">
    <div class="full">
        <label>Party name / username</label>
        <input name="q" value="{{ $search }}" placeholder="Name, connection ID, MikroTik username, phone or MAC">
    </div>
    <div>
        <label>Date from</label>
        <input type="date" name="from" value="{{ $from->toDateString() }}">
    </div>
    <div>
        <label>Date to</label>
        <input type="date" name="to" value="{{ $to->toDateString() }}">
    </div>
    <div>
        <label>Stability</label>
        <select name="stability">
            <option value="">All parties</option>
            <option value="unstable" @selected($unstableOnly)>Not stable only ({{ $unstableCount }})</option>
        </select>
    </div>
    <div>
        <label>Swing threshold (dB)</label>
        <input type="number" name="swing" min="0.1" max="40" step="0.1" value="{{ $swing + 0 }}">
    </div>
    <div>
        <label>Latest Rx power</label>
        <select name="power_op">
            <option value="">Any</option>
            <option value="lt" @selected($powerOp === 'lt')>Less than (&lt;)</option>
            <option value="gt" @selected($powerOp === 'gt')>Greater than (&gt;)</option>
        </select>
    </div>
    <div>
        <label>Rx power value (dBm)</label>
        <input type="number" name="power_dbm" min="-40" max="5" step="0.1" placeholder="-25" value="{{ $powerValue !== null ? $powerValue + 0 : '' }}">
    </div>
    <div class="full actions">
        <button class="btn secondary" type="submit">Search</button>
        <a class="btn light" href="{{ route('troubleshoot.onu-signal') }}">Reset</a>
    </div>
</form>

<div class="onu-allsig__bar">
    <span class="muted">Showing {{ $pagination?->firstItem() ?? 0 }}–{{ $pagination?->lastItem() ?? 0 }} of {{ $pagination?->total() ?? 0 }} parties@if ($unstableOnly) · not stable@endif @if ($powerActive) · latest Rx {{ $powerOp === 'lt' ? '<' : '>' }} {{ $powerValue + 0 }} dBm @endif</span>
    <form method="post" action="{{ route('onu-signal.visibility.update') }}" class="onu-signal__show">
        @csrf
        @method('patch')
        <span class="muted">Show on every graph:</span>
        <label><input type="checkbox" name="show_rx" value="1" @checked($showRx)> Rx</label>
        <label><input type="checkbox" name="show_tx" value="1" @checked($showTx)> Tx</label>
        <noscript><button class="btn light" type="submit">Save</button></noscript>
    </form>
    @include('partials.per_page')
</div>

@forelse ($parties as $party)
    @php
        $sl = ($pagination?->firstItem() ?? 1) + $loop->index;
        $win = $party->onuSamplesWindow;
        $rxWin = $win->pluck('rx_power_dbm')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
        $latest = $win->last();
        $onu = $party->matched_onu ?? null;
        $rxSwing = $rxWin->count() > 1 ? (float) ($rxWin->max() - $rxWin->min()) : 0.0;
        $displaySwing = round($rxSwing, 2);
        $hasSwing = $displaySwing > 0;
        $latestRx = $latest?->rx_power_dbm !== null ? (float) $latest->rx_power_dbm : null;
        $powerBadge = match (true) {
            $latestRx === null => null,
            $latestRx > -10 => 'power very high',
            $latestRx > -15 => 'power high',
            $latestRx < -30 => 'power very low',
            $latestRx < -25 => 'power low',
            default => null,
        };
        $powerBadgeClass = in_array($powerBadge, ['power very high', 'power very low'], true) ? 'power-danger' : 'power-warning';
        $subParts = array_values(array_filter([
            $party->connection_id ?: '#'.$party->id,
            ($party->mikrotik_username && $party->mikrotik_username !== $party->connection_id) ? $party->mikrotik_username : null,
            $party->last_connected_mac ?: null,
        ]));
    @endphp
    <section class="onu-allsig__card">
        <div class="onu-allsig__heading">
            <h2>
                <span class="onu-allsig__sl">{{ $sl }}.</span>
                <a href="{{ route('customers.show', $party) }}">{{ $party->name }}</a>
                <span class="muted">· {{ implode(' · ', $subParts) }}</span>
                <span class="onu-allsig__badges">
                    @if ($powerBadge)<span class="badge {{ $powerBadgeClass }}">{{ $powerBadge }}</span>@endif
                    @if ($party->onu_unstable ?? false)<span class="badge unstable">not stable</span>@endif
                </span>
            </h2>
            @if (auth()->user()?->hasPermission('manage_tickets') && auth()->user()?->canAccessMenu('tickets'))
                @php($isUnstable = (bool) ($party->onu_unstable ?? false))
                @php($isPowerDanger = $powerBadgeClass === 'power-danger' && $powerBadge)
                @php($isPowerWarn = $powerBadgeClass === 'power-warning' && $powerBadge)
                @php($ticketReason = match (true) {
                    $isUnstable => 'This party is not stable — open a ticket',
                    $isPowerDanger => 'ONU power is far out of band — open a ticket',
                    $isPowerWarn => 'ONU power is out of band — open a ticket',
                    default => 'This party looks stable',
                })
                <a @class([
                        'btn onu-ticket__button',
                        'onu-ticket__button--alert' => $isUnstable || $isPowerDanger,
                        'onu-ticket__button--warn' => ! $isUnstable && ! $isPowerDanger && $isPowerWarn,
                        'onu-ticket__button--muted' => ! $isUnstable && ! $isPowerDanger && ! $isPowerWarn,
                    ]) href="{{ route('tickets.onu-signal.create', [
                        'customer' => $party,
                        'from' => $from->toDateString(),
                        'to' => $to->toDateString(),
                        'swing' => $swing + 0,
                    ]) }}" title="{{ $ticketReason }} · বাংলা সিগন্যাল বিস্তারিতসহ editable ticket form খুলুন">Add Ticket</a>
            @endif
        </div>
        <div class="onu-allsig__meta">
            @if ($onu)
                <div class="onu-allsig__metric"><span class="onu-allsig__metric-label">ONU Serial</span><b>{{ $onu->mac_address ?: '—' }}</b></div>
                <div class="onu-allsig__metric"><span class="onu-allsig__metric-label">OLT</span><b>{{ $onu->olt_name ?: '—' }}</b></div>
                <div class="onu-allsig__metric"><span class="onu-allsig__metric-label">PON / ONU</span><b>{{ $onu->pon_port }}/{{ $onu->onu_id }}</b></div>
                <div @class(['onu-allsig__metric', 'onu-allsig__metric--online' => mb_strtolower((string) $onu->status) === 'online'])>
                    <span class="onu-allsig__metric-label">ONU Status</span><b>{{ $onu->status ?: '—' }}</b>
                </div>
            @else
                <div class="onu-allsig__metric onu-allsig__metric--wide"><span class="onu-allsig__metric-label">ONU Match</span><b>No matching OLT ONU for this MAC.</b></div>
            @endif
            <div class="onu-allsig__metric"><span class="onu-allsig__metric-label">Samples</span><b>{{ $win->count() }}</b></div>
            @if ($rxWin->isNotEmpty())
                <div class="onu-allsig__metric">
                    <span class="onu-allsig__metric-label">Rx Min / Max</span>
                    <b>{{ number_format($rxWin->min(), 2) }} / {{ number_format($rxWin->max(), 2) }} dBm</b>
                </div>
                <div @class(['onu-allsig__metric', 'onu-allsig__metric--swing' => $hasSwing])>
                    <span class="onu-allsig__metric-label">Swing</span><b>{{ number_format($rxSwing, 2) }} dB</b>
                    @if ($hasSwing)<small>Rx power changed during this period</small>@endif
                </div>
            @endif
            @if ($latest)
                <div class="onu-allsig__metric onu-allsig__metric--wide">
                    <span class="onu-allsig__metric-label">Latest Sample</span>
                    <b>{{ $latest->sampled_at->format('d/m/Y H:i') }}</b>
                    <span class="onu-allsig__latest-values">
                        @if ($latest->rx_power_dbm !== null)<span>Rx {{ number_format((float) $latest->rx_power_dbm, 2) }} dBm</span>@endif
                        @if ($latest->tx_power_dbm !== null)<span>Tx {{ number_format((float) $latest->tx_power_dbm, 2) }} dBm</span>@endif
                    </span>
                </div>
            @endif
        </div>
        <div class="onu-allsig__chart">
            @include('customers._onu_signal_chart', [
                'samples' => $party->onuSamplesWindow,
                'showRx' => $showRx,
                'showTx' => $showTx,
                'showLegend' => true,
            ])
        </div>
    </section>
@empty
    <div class="card"><p class="muted" style="margin:0">
        @if ($search !== '' || $unstableOnly)
            এই ফিল্টারে কোনো পার্টির ONU signal নমুনা পাওয়া যায়নি। তারিখ বা সার্চ পরিবর্তন করে দেখুন।
        @else
            এখনো কোনো পার্টির ONU signal নমুনা জমা হয়নি। সংগ্রহ শুরু হলে (প্রতি {{ $intervalHours }}h) এখানে সব পার্টির গ্রাফ দেখা যাবে।
        @endif
    </p></div>
@endforelse

@if ($pagination && $pagination->hasPages())
    <div style="margin-top:16px">{{ $pagination->links() }}</div>
@endif
@endsection
