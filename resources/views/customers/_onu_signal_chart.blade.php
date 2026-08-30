@php
    /** @var \Illuminate\Support\Collection $samples */
    $samples = ($samples ?? collect())->filter(fn ($s) => $s->rx_power_dbm !== null || $s->tx_power_dbm !== null)->values();

    $W = 720; $H = 220;
    $padL = 40; $padR = 12; $padT = 14; $padB = 24;
    $plotW = $W - $padL - $padR;
    $plotH = $H - $padT - $padB;

    $rxVals = $samples->pluck('rx_power_dbm')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
    $txVals = $samples->pluck('tx_power_dbm')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
    $allVals = $rxVals->merge($txVals);

    $yMin = $allVals->isEmpty() ? -30 : floor(min($allVals->min(), -26) - 2);
    $yMax = $allVals->isEmpty() ? 5 : ceil(max($allVals->max(), -14) + 2);
    $yMin = max($yMin, -45); $yMax = min($yMax, 12);
    $span = max(1, $yMax - $yMin);

    $tsMin = $samples->min(fn ($s) => $s->sampled_at->getTimestamp()) ?? 0;
    $tsMax = $samples->max(fn ($s) => $s->sampled_at->getTimestamp()) ?? 1;
    $tsSpan = max(1, $tsMax - $tsMin);

    $x = fn ($ts) => $padL + ($tsSpan ? ($ts - $tsMin) / $tsSpan : 0) * $plotW;
    $y = fn ($v) => $padT + ($yMax - $v) / $span * $plotH;

    $line = function ($key) use ($samples, $x, $y) {
        $pts = [];
        foreach ($samples as $s) {
            if ($s->{$key} === null) { continue; }
            $pts[] = round($x($s->sampled_at->getTimestamp()), 1).','.round($y((float) $s->{$key}), 1);
        }
        return implode(' ', $pts);
    };

    $latest = $samples->last();
@endphp

@if ($samples->count() < 2)
    <p class="muted" style="margin:0">এখনো যথেষ্ট নমুনা জমা হয়নি। প্রতি নির্ধারিত ঘণ্টায় পার্টির ONU Rx/Tx power সংরক্ষণ হয়; কয়েকটি নমুনা জমা হলে এখানে গ্রাফ দেখা যাবে।</p>
@else
    <div class="onu-chart">
        <div class="onu-chart__legend">
            <span><i style="background:#1d76c9"></i> Rx (dBm)</span>
            <span><i style="background:#0f7a55"></i> Tx (dBm)</span>
            @if ($latest)
                <span class="muted">সর্বশেষ {{ $latest->sampled_at->format('d/m H:i') }} — Rx {{ $latest->rx_power_dbm !== null ? number_format((float) $latest->rx_power_dbm, 2) : '—' }} / Tx {{ $latest->tx_power_dbm !== null ? number_format((float) $latest->tx_power_dbm, 2) : '—' }}</span>
            @endif
        </div>
        <svg viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" role="img" aria-label="ONU signal history">
            {{-- horizontal grid + y labels --}}
            @foreach ([$yMax, ($yMax + $yMin) / 2, $yMin] as $gv)
                <line x1="{{ $padL }}" y1="{{ round($y($gv), 1) }}" x2="{{ $W - $padR }}" y2="{{ round($y($gv), 1) }}" stroke="#e6ebf2" stroke-width="1"/>
                <text x="{{ $padL - 6 }}" y="{{ round($y($gv), 1) + 3 }}" text-anchor="end" font-size="10" fill="#94a3b8">{{ round($gv) }}</text>
            @endforeach
            {{-- -15 / -25 signal band --}}
            @if ($yMin <= -25 && $yMax >= -15)
                <rect x="{{ $padL }}" y="{{ round($y(-15), 1) }}" width="{{ $plotW }}" height="{{ round($y(-25) - $y(-15), 1) }}" fill="#0f7a55" fill-opacity="0.06"/>
                <line x1="{{ $padL }}" y1="{{ round($y(-15), 1) }}" x2="{{ $W - $padR }}" y2="{{ round($y(-15), 1) }}" stroke="#94a3b8" stroke-width="1" stroke-dasharray="3 3"/>
                <line x1="{{ $padL }}" y1="{{ round($y(-25), 1) }}" x2="{{ $W - $padR }}" y2="{{ round($y(-25), 1) }}" stroke="#d92d20" stroke-width="1" stroke-dasharray="3 3"/>
            @endif
            @if ($line('tx_power_dbm') !== '')
                <polyline points="{{ $line('tx_power_dbm') }}" fill="none" stroke="#0f7a55" stroke-width="1.6" stroke-linejoin="round"/>
            @endif
            @if ($line('rx_power_dbm') !== '')
                <polyline points="{{ $line('rx_power_dbm') }}" fill="none" stroke="#1d76c9" stroke-width="2" stroke-linejoin="round"/>
            @endif
            <text x="{{ $padL }}" y="{{ $H - 6 }}" font-size="10" fill="#94a3b8">{{ $samples->first()->sampled_at->format('d/m H:i') }}</text>
            <text x="{{ $W - $padR }}" y="{{ $H - 6 }}" text-anchor="end" font-size="10" fill="#94a3b8">{{ $samples->last()->sampled_at->format('d/m H:i') }}</text>
        </svg>
    </div>
@endif
