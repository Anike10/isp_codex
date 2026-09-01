@extends('layouts.app')

@section('content')
@php
    // Sections opened on load — the small, always-useful ones plus a
    // custom command result. Everything else starts collapsed.
    $openByDefault = ['Recent log', 'System resource', 'Identity', 'PPP active'];
    $tailSections = ['/log/print'];
@endphp

<style>
    .rd-page { font-size:13.5px; }
    .rd-router { padding:0; overflow:hidden; }
    .rd-router-head {
        display:flex; align-items:center; gap:10px; flex-wrap:wrap;
        padding:13px 16px; border-bottom:1px solid var(--line); background:#f8fbff;
    }
    .rd-router-head h2 { margin:0; font-size:16px; }
    .rd-router-head .rd-ip { font-size:13px; color:var(--muted); font-variant-numeric:tabular-nums; }
    .rd-router-body { padding:12px 16px 16px; display:flex; flex-direction:column; gap:8px; }

    .rd-section { border:1px solid var(--line); border-radius:9px; background:#fff; }
    .rd-section > summary {
        list-style:none; cursor:pointer; padding:11px 14px; display:flex; align-items:center;
        gap:10px; font-weight:700; user-select:none;
    }
    .rd-section[open] > summary { border-bottom:1px solid var(--line); }
    .rd-section > summary:hover { background:#f7fafc; }
    .rd-section > summary::-webkit-details-marker { display:none; }
    .rd-section > summary::before {
        content:""; width:7px; height:7px; border-right:2px solid #94a3b8; border-bottom:2px solid #94a3b8;
        transform:rotate(-45deg); transition:transform .15s ease; flex:none;
    }
    .rd-section[open] > summary::before { transform:rotate(45deg); }
    .rd-section > summary .rd-label { font-size:13.5px; }
    .rd-section > summary .rd-cmd { color:var(--muted); font-weight:500; font-size:12px; font-family:ui-monospace,Menlo,Consolas,monospace; }
    .rd-section > summary .rd-count {
        margin-left:auto; font-weight:700; font-size:11px; color:#475467; background:#eef2f7;
        border-radius:999px; padding:2px 9px; white-space:nowrap;
    }
    .rd-section > summary .rd-count.is-error { color:#fff; background:var(--danger); }
    .rd-section__body { padding:12px 14px; }

    /* Key / value (single-record) */
    .rd-kv { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 26px; margin:0; }
    .rd-kv__row {
        display:grid; grid-template-columns:minmax(120px,38%) 1fr; gap:12px;
        padding:7px 0; border-bottom:1px solid var(--zebra);
    }
    .rd-kv__row dt { color:var(--muted); font-weight:600; word-break:break-word; }
    .rd-kv__row dd { margin:0; font-weight:600; word-break:break-word; }
    .rd-kv__row dd code { background:#f1f5f9; padding:1px 5px; border-radius:4px; font-size:12.5px; }

    /* Multi-row tables */
    .rd-scroll { overflow:auto; max-height:460px; border:1px solid var(--line); border-radius:8px; }
    table.rd-rows { width:100%; border-collapse:separate; border-spacing:0; border:0; background:#fff; }
    table.rd-rows th {
        position:sticky; top:0; z-index:1; background:#eef2f7; color:#475467;
        font-size:11px; text-transform:uppercase; letter-spacing:.3px; white-space:nowrap;
        text-align:left; padding:8px 10px; border-bottom:1px solid var(--line);
    }
    table.rd-rows td {
        padding:6px 10px; border-bottom:1px solid var(--zebra); vertical-align:top;
        white-space:normal; word-break:break-word; max-width:360px; line-height:1.4;
    }
    table.rd-rows tbody tr:nth-child(even) td { background:#fafcfe; }
    table.rd-rows tbody tr:hover td { background:#f2f8ff; }
    table.rd-rows tbody tr:last-child td { border-bottom:0; }
    table.rd-rows td code, .rd-kv__row dd code {
        font-family:ui-monospace,Menlo,Consolas,monospace; font-size:12px;
        background:#f1f5f9; padding:1px 5px; border-radius:4px;
    }
    table.rd-rows .muted { font-weight:400; }

    @media (max-width:820px) {
        .rd-kv { grid-template-columns:1fr; }
    }
</style>

<div class="topbar">
    <div>
        <h1>Troubleshoot &mdash; Router Live Data</h1>
        <div class="muted">
            Read-only <code>/print</code> pulls from every active MikroTik router, live. Nothing here changes a router.
            Fetched {{ $fetchedAt->format('d/m/Y H:i:s') }}.
        </div>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('troubleshoot.router-data', $command !== '' ? ['command' => $command] : []) }}">Refresh</a>
    </div>
</div>

<form method="get" class="card" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-bottom:16px">
    <div style="flex:1;min-width:260px">
        <label class="muted" style="display:block;font-size:12px">Extra command &mdash; any read-only path ending in <code>/print</code></label>
        <input type="text" name="command" value="{{ $command }}" placeholder="/interface/ethernet/print" style="width:100%">
    </div>
    <button class="btn" type="submit">Run on all routers</button>
    @if ($command !== '')
        <a class="btn light" href="{{ route('troubleshoot.router-data') }}">Clear</a>
    @endif
    @if ($commandError)
        <div class="alert error" style="flex-basis:100%;margin:0">{{ $commandError }}</div>
    @endif
</form>

<div class="rd-page">
@if ($routers->isEmpty())
    <div class="card"><p class="muted" style="margin:0">No active MikroTik routers. Enable a router under Network &rarr; MikroTik Routers.</p></div>
@else
    @foreach ($results as $entry)
        @php
            $router = $entry['router'];
            $commandList = $command !== '' ? $sectionMap + ['Custom: '.$command => $command] : $sectionMap;
            $okCount = collect($entry['sections'])->filter(fn ($s) => ! isset($s['error']))->count();
            $errCount = count($entry['sections']) - $okCount;
        @endphp
        <section class="card rd-router" style="margin-bottom:16px">
            <div class="rd-router-head">
                <h2>{{ $router->name }}</h2>
                <span class="rd-ip">{{ $router->ip_address }}:{{ $router->api_port }}</span>
                <span class="badge {{ $router->usesRestTransport() ? 'partial' : 'inactive' }}">{{ $router->usesRestTransport() ? 'REST' : 'API' }}</span>
                @if ($router->read_only)
                    <span class="badge draft">read-only</span>
                @endif
                @if ($errCount > 0)
                    <span class="badge overdue" style="margin-left:auto">{{ $errCount }} section(s) failed</span>
                @else
                    <span class="badge active" style="margin-left:auto">all sections read</span>
                @endif
            </div>

            <div class="rd-router-body">
                @foreach ($commandList as $label => $cmd)
                    @php
                        $res = $entry['sections'][$cmd] ?? null;
                        $error = $res['error'] ?? null;
                        $records = $res['records'] ?? [];
                        $count = is_countable($records) ? count($records) : 0;
                        $isCustom = str_starts_with($label, 'Custom: ');
                        $open = $isCustom || in_array($label, $openByDefault, true);
                    @endphp
                    <details class="rd-section" @if ($open) open @endif>
                        <summary>
                            <span class="rd-label">{{ $label }}</span>
                            <span class="rd-cmd">{{ $cmd }}</span>
                            <span class="rd-count {{ $error ? 'is-error' : '' }}">{{ $error ? 'error' : ($count === 1 ? '1 record' : number_format($count).' rows') }}</span>
                        </summary>
                        <div class="rd-section__body">
                            @if ($error)
                                <div class="alert error" style="margin:0">{{ $error }}</div>
                            @else
                                @include('troubleshoot._records_table', [
                                    'records' => $records,
                                    'rowCap' => $rowCap,
                                    'tail' => in_array($cmd, $tailSections, true),
                                ])
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        </section>
    @endforeach
@endif
</div>
@endsection
