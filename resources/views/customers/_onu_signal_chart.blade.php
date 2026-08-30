@php
    /** @var \Illuminate\Support\Collection $samples */
    $samples = ($samples ?? collect())->filter(fn ($s) => $s->rx_power_dbm !== null || $s->tx_power_dbm !== null)->values();

    // Which series the operator has enabled from the ONU Signal History card.
    $showRx = $showRx ?? true;
    $showTx = $showTx ?? false;
    $showLegend = $showLegend ?? true;

    $W = 720; $H = 220;
    $padL = 40; $padR = 12; $padT = 14; $padB = 24;
    $plotW = $W - $padL - $padR;
    $plotH = $H - $padT - $padB;

    $rxVals = $showRx ? $samples->pluck('rx_power_dbm')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v) : collect();
    $txVals = $showTx ? $samples->pluck('tx_power_dbm')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v) : collect();
    $allVals = $rxVals->merge($txVals);

    $yMin = $allVals->isEmpty() ? -30 : floor(min($allVals->min(), -26) - 2);
    $yMax = $allVals->isEmpty() ? 5 : ceil(max($allVals->max(), -14) + 2);
    $yMin = max($yMin, -45); $yMax = min($yMax, 12);
    $span = max(1, $yMax - $yMin);

    $tsMin = $samples->min(fn ($s) => $s->sampled_at->getTimestamp()) ?? 0;
    $tsMax = $samples->max(fn ($s) => $s->sampled_at->getTimestamp()) ?? 1;
    $tsSpan = $tsMax - $tsMin;

    // With a single sample (or all at the same instant) there is no time span to
    // map across, so pin every point to the middle of the plot.
    $x = fn ($ts) => $tsSpan > 0 ? $padL + ($ts - $tsMin) / $tsSpan * $plotW : $padL + $plotW / 2;
    $y = fn ($v) => $padT + ($yMax - $v) / $span * $plotH;

    $points = function ($key) use ($samples, $x, $y) {
        $pts = [];
        foreach ($samples as $s) {
            if ($s->{$key} === null) { continue; }
            $pts[] = [round($x($s->sampled_at->getTimestamp()), 1), round($y((float) $s->{$key}), 1)];
        }
        return $pts;
    };

    $rxPts = $showRx ? $points('rx_power_dbm') : [];
    $txPts = $showTx ? $points('tx_power_dbm') : [];
    $polyline = fn ($pts) => implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $pts));

    // Per-sample rows for the hover tooltip: [x, "d/m/Y H:i", rx, tx, yRx, yTx].
    $jsRows = [];
    foreach ($samples as $s) {
        $rxv = $s->rx_power_dbm !== null ? (float) $s->rx_power_dbm : null;
        $txv = $s->tx_power_dbm !== null ? (float) $s->tx_power_dbm : null;
        $jsRows[] = [
            round($x($s->sampled_at->getTimestamp()), 2),
            $s->sampled_at->format('d/m/Y H:i'),
            $rxv,
            $txv,
            $rxv !== null ? round($y($rxv), 2) : null,
            $txv !== null ? round($y($txv), 2) : null,
        ];
    }

    $latest = $samples->last();
    $bandVisible = $yMin <= -25 && $yMax >= -15;
    $cid = 'onuChart_'.uniqid();
@endphp

@if (! $showRx && ! $showTx)
    <p class="muted" style="margin:0">গ্রাফে দেখানোর জন্য Rx বা Tx কোনোটিই নির্বাচন করা নেই। উপরের "Show" থেকে চালু করুন।</p>
@elseif ($samples->isEmpty())
    <p class="muted" style="margin:0">এখনো কোনো নমুনা জমা হয়নি। প্রতি নির্ধারিত ঘণ্টায় পার্টির ONU Rx/Tx power সংরক্ষণ হয় — কয়েক ঘণ্টা পর এখানে পূর্ণ গ্রাফ দেখা যাবে।</p>
@else
    <style>
        .onu-chart { position:relative; }
        .onu-chart__toolbar { display:flex; justify-content:flex-end; align-items:center; gap:8px; font-size:12px; color:#667085; margin-bottom:6px; }
        .onu-chart__toolbar input[type=range] { vertical-align:middle; width:130px; }
        .onu-chart__zoomreset { border:1px solid #d0d5dd; background:#fff; border-radius:6px; padding:1px 8px; cursor:pointer; font-size:12px; }
        .onu-chart__scroll { overflow-x:auto; overflow-y:hidden; }
        .onu-chart__scroll svg { height:220px; width:100%; display:block; }
        .onu-chart__hit { cursor:crosshair; }
        .onu-chart__tip { position:absolute; z-index:5; pointer-events:none; background:#101828; color:#fff; font-size:11px; line-height:1.45; padding:5px 8px; border-radius:6px; white-space:nowrap; box-shadow:0 4px 12px rgba(16,24,40,.25); }
        .onu-chart__tip[hidden] { display:none; }
    </style>
    <div class="onu-chart" id="{{ $cid }}">
        @if ($showLegend)
            <div class="onu-chart__legend">
                @if ($showRx)
                    <span><i style="background:#1d76c9"></i> Rx (dBm)</span>
                @endif
                @if ($showTx)
                    <span><i style="background:#0f7a55"></i> Tx (dBm)</span>
                @endif
                @if ($latest)
                    <span class="muted">সর্বশেষ {{ $latest->sampled_at->format('d/m H:i') }}
                        @if ($showRx) — Rx {{ $latest->rx_power_dbm !== null ? number_format((float) $latest->rx_power_dbm, 2) : '—' }} @endif
                        @if ($showTx) / Tx {{ $latest->tx_power_dbm !== null ? number_format((float) $latest->tx_power_dbm, 2) : '—' }} @endif
                    </span>
                @endif
            </div>
        @endif
        @if ($samples->count() >= 2)
            <div class="onu-chart__toolbar">
                <label>Zoom <input type="range" class="onu-chart__zoom" min="1" max="8" step="0.25" value="1"></label>
                <button type="button" class="onu-chart__zoomreset">Reset</button>
            </div>
        @endif
        <div class="onu-chart__scroll">
            <svg viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" role="img" aria-label="ONU signal history">
                {{-- horizontal grid + y labels --}}
                @foreach ([$yMax, ($yMax + $yMin) / 2, $yMin] as $gv)
                    <line x1="{{ $padL }}" y1="{{ round($y($gv), 1) }}" x2="{{ $W - $padR }}" y2="{{ round($y($gv), 1) }}" stroke="#e6ebf2" stroke-width="1"/>
                    <text x="{{ $padL - 6 }}" y="{{ round($y($gv), 1) + 3 }}" text-anchor="end" font-size="10" fill="#94a3b8">{{ round($gv) }}</text>
                @endforeach
                {{-- -15 / -25 signal band --}}
                @if ($bandVisible)
                    <rect x="{{ $padL }}" y="{{ round($y(-15), 1) }}" width="{{ $plotW }}" height="{{ round($y(-25) - $y(-15), 1) }}" fill="#0f7a55" fill-opacity="0.06"/>
                    <line x1="{{ $padL }}" y1="{{ round($y(-15), 1) }}" x2="{{ $W - $padR }}" y2="{{ round($y(-15), 1) }}" stroke="#94a3b8" stroke-width="1" stroke-dasharray="3 3"/>
                    <line x1="{{ $padL }}" y1="{{ round($y(-25), 1) }}" x2="{{ $W - $padR }}" y2="{{ round($y(-25), 1) }}" stroke="#d92d20" stroke-width="1" stroke-dasharray="3 3"/>
                @endif
                {{-- Tx --}}
                @if (count($txPts) >= 2)
                    <polyline points="{{ $polyline($txPts) }}" fill="none" stroke="#0f7a55" stroke-width="1.6" stroke-linejoin="round"/>
                @endif
                @foreach ($txPts as $p)
                    <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="{{ count($txPts) === 1 ? 3.2 : 1.8 }}" fill="#0f7a55"/>
                @endforeach
                {{-- Rx --}}
                @if (count($rxPts) >= 2)
                    <polyline points="{{ $polyline($rxPts) }}" fill="none" stroke="#1d76c9" stroke-width="2" stroke-linejoin="round"/>
                @endif
                @foreach ($rxPts as $p)
                    <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="{{ count($rxPts) === 1 ? 3.6 : 2 }}" fill="#1d76c9"/>
                @endforeach
                <text x="{{ $padL }}" y="{{ $H - 6 }}" font-size="10" fill="#94a3b8">{{ $samples->first()->sampled_at->format('d/m H:i') }}</text>
                <text x="{{ $W - $padR }}" y="{{ $H - 6 }}" text-anchor="end" font-size="10" fill="#94a3b8">{{ $samples->last()->sampled_at->format('d/m H:i') }}</text>
                {{-- hover overlay --}}
                <rect class="onu-chart__hit" x="{{ $padL }}" y="{{ $padT }}" width="{{ $plotW }}" height="{{ $plotH }}" fill="transparent"/>
                <g class="onu-chart__cursor" pointer-events="none" style="display:none">
                    <line class="onu-chart__cursor-line" y1="{{ $padT }}" y2="{{ $padT + $plotH }}" stroke="#98a2b3" stroke-width="1" stroke-dasharray="3 3"/>
                    <circle class="onu-chart__cursor-rx" r="3.5" fill="#1d76c9" style="display:none"/>
                    <circle class="onu-chart__cursor-tx" r="3.5" fill="#0f7a55" style="display:none"/>
                </g>
            </svg>
        </div>
        <div class="onu-chart__tip" hidden></div>
        <script type="application/json" class="onu-chart__data">@json($jsRows)</script>
        <script>
        (function () {
            var root = document.getElementById(@json($cid));
            if (!root || root.dataset.wired) return;
            root.dataset.wired = '1';
            var svg = root.querySelector('svg');
            var zoom = root.querySelector('.onu-chart__zoom');
            var reset = root.querySelector('.onu-chart__zoomreset');
            var tip = root.querySelector('.onu-chart__tip');
            var cursor = root.querySelector('.onu-chart__cursor');
            var cLine = root.querySelector('.onu-chart__cursor-line');
            var cRx = root.querySelector('.onu-chart__cursor-rx');
            var cTx = root.querySelector('.onu-chart__cursor-tx');
            var hit = root.querySelector('.onu-chart__hit');
            var rows = JSON.parse(root.querySelector('.onu-chart__data').textContent || '[]');
            var showRx = @json((bool) $showRx), showTx = @json((bool) $showTx);

            function applyZoom() { if (zoom) svg.style.width = (parseFloat(zoom.value) * 100) + '%'; }
            if (zoom) { zoom.addEventListener('input', applyZoom); applyZoom(); }
            if (reset) reset.addEventListener('click', function () { zoom.value = 1; applyZoom(); });

            function nearest(clientX) {
                var pt = svg.createSVGPoint(); pt.x = clientX; pt.y = 0;
                var m = svg.getScreenCTM(); if (!m) return null;
                var loc = pt.matrixTransform(m.inverse());
                var best = null, bd = Infinity;
                for (var i = 0; i < rows.length; i++) {
                    var d = Math.abs(rows[i][0] - loc.x);
                    if (d < bd) { bd = d; best = rows[i]; }
                }
                return best;
            }
            function move(e) {
                var t = (e.touches && e.touches[0]) || e;
                var r = nearest(t.clientX);
                if (!r) return;
                cursor.style.display = '';
                cLine.setAttribute('x1', r[0]); cLine.setAttribute('x2', r[0]);
                if (showRx && r[4] != null) { cRx.style.display = ''; cRx.setAttribute('cx', r[0]); cRx.setAttribute('cy', r[4]); } else { cRx.style.display = 'none'; }
                if (showTx && r[5] != null) { cTx.style.display = ''; cTx.setAttribute('cx', r[0]); cTx.setAttribute('cy', r[5]); } else { cTx.style.display = 'none'; }
                var html = '<b>' + r[1] + '</b>';
                if (showRx) html += '<br>Rx ' + (r[2] != null ? r[2].toFixed(2) + ' dBm' : '—');
                if (showTx) html += '<br>Tx ' + (r[3] != null ? r[3].toFixed(2) + ' dBm' : '—');
                tip.innerHTML = html; tip.hidden = false;
                var cr = root.getBoundingClientRect();
                var lx = t.clientX - cr.left + 12, ly = t.clientY - cr.top + 12;
                if (lx + tip.offsetWidth > cr.width) lx = t.clientX - cr.left - tip.offsetWidth - 12;
                tip.style.left = Math.max(0, lx) + 'px';
                tip.style.top = ly + 'px';
            }
            function leave() { tip.hidden = true; cursor.style.display = 'none'; }
            hit.addEventListener('mousemove', move);
            hit.addEventListener('mouseleave', leave);
            hit.addEventListener('touchstart', move, { passive: true });
            hit.addEventListener('touchmove', move, { passive: true });
            hit.addEventListener('touchend', leave);
        })();
        </script>
        @if ($samples->count() < 2)
            <p class="muted" style="margin:8px 0 0">একটি নমুনা জমা হয়েছে। প্রতি ঘণ্টায় নতুন নমুনা যোগ হয়ে এটি সম্পূর্ণ লাইন গ্রাফে পরিণত হবে।</p>
        @endif
    </div>
@endif
