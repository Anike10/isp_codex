@extends('layouts.app')

@section('content')
@php
    // Sections opened on load — the small, always-useful ones plus a
    // custom command result. Everything else starts collapsed.
    $openByDefault = ['Recent log', 'System resource', 'Identity', 'PPP active'];
    $tailSections = ['/log/print'];
@endphp

<style>
    .rd-router-head { display:flex; align-items:baseline; gap:10px; flex-wrap:wrap; margin-bottom:6px; }
    .rd-router-head h2 { margin:0; font-size:18px; }
    .rd-router-head .muted { font-size:13px; }
    .rd-section { border:1px solid var(--line); border-radius:8px; margin-top:10px; background:#fff; }
    .rd-section > summary {
        list-style:none; cursor:pointer; padding:10px 14px; display:flex; align-items:center;
        gap:10px; font-weight:700; user-select:none;
    }
    .rd-section > summary::-webkit-details-marker { display:none; }
    .rd-section > summary::before {
        content:""; width:7px; height:7px; border-right:2px solid #94a3b8; border-bottom:2px solid #94a3b8;
        transform:rotate(-45deg); transition:transform .15s ease; flex:none;
    }
    .rd-section[open] > summary::before { transform:rotate(45deg); }
    .rd-section > summary .rd-cmd { color:var(--muted); font-weight:500; font-size:12px; }
    .rd-section > summary .rd-count { margin-left:auto; font-weight:600; font-size:12px; color:var(--muted); }
    .rd-section > summary .rd-count.is-error { color:var(--danger); }
    .rd-section__body { padding:0 14px 14px; }
    table.rd-kv th { width:230px; text-align:left; color:var(--muted); font-weight:600; vertical-align:top; }
    table.rd-kv td, table.rd-rows td { white-space:pre-wrap; word-break:break-word; }
    table.rd-rows td { max-width:380px; }
    table.rd-rows th { position:sticky; top:0; }
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

@include('troubleshoot._tabs', ['active' => 'router-data'])

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

@if ($routers->isEmpty())
    <div class="card"><p class="muted" style="margin:0">No active MikroTik routers. Enable a router under Network &rarr; MikroTik Routers.</p></div>
@else
    @foreach ($results as $entry)
        @php
            $router = $entry['router'];
            $commandList = $command !== '' ? $sectionMap + ['Custom: '.$command => $command] : $sectionMap;
        @endphp
        <section class="card" style="margin-bottom:16px">
            <div class="rd-router-head">
                <h2>{{ $router->name }}</h2>
                <span class="muted">{{ $router->ip_address }}:{{ $router->api_port }}</span>
                <span class="badge {{ $router->usesRestTransport() ? 'partial' : 'inactive' }}">{{ $router->usesRestTransport() ? 'REST' : 'API' }}</span>
                @if ($router->read_only)
                    <span class="badge draft">read-only</span>
                @endif
            </div>

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
                        <span>{{ $label }}</span>
                        <span class="rd-cmd">{{ $cmd }}</span>
                        <span class="rd-count {{ $error ? 'is-error' : '' }}">
                            {{ $error ? 'error' : ($count === 1 ? '1 record' : number_format($count).' rows') }}
                        </span>
                    </summary>
                    <div class="rd-section__body">
                        @if ($error)
                            <div class="alert error" style="margin:8px 0 0">{{ $error }}</div>
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
        </section>
    @endforeach
@endif
@endsection
