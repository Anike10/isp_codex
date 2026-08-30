@php
    /** @var \Illuminate\Support\Collection $samples */
    $samples = ($samples ?? collect())->filter(fn ($s) => $s->rx_power_dbm !== null || $s->tx_power_dbm !== null)->values();

    // Which series the operator has enabled on the OLT ONUs settings form.
    $showRx = $showRx ?? true;
    $showTx = $showTx ?? false;

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

    $latest = $samples->last();
    $bandVisible = $yMin <= -25 && $yMax >= -15;
@endphp

@if (! $showRx && ! $showTx)
    <p class="muted" style="margin:0">গ্রাফে দেখানোর জন্য Rx বা Tx কোনোটিই নির্বাচন করা নেই। OLT ONUs পেজের সেটিংস থেকে চালু করুন।</p>
@elseif ($samples->isEmpty())
    <p class="muted" style="margin:0">এখনো কোনো নমুনা জমা হয়নি। প্রতি নির্ধারিত ঘণ্টায় পার্টির ONU Rx/Tx power সংরক্ষণ হয় — কয়েক ঘণ্টা পর এখানে পূর্ণ গ্রাফ দেখা যাবে।</p>
@else
    <div class="onu-chart">
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
        </svg>
        @if ($samples->count() < 2)
            <p class="muted" style="margin:8px 0 0">একটি নমুনা জমা হয়েছে। প্রতি ঘণ্টায় নতুন নমুনা যোগ হয়ে এটি সম্পূর্ণ লাইন গ্রাফে পরিণত হবে।</p>
        @endif
    </div>
@endif
