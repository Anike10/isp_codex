@php
    $reason = trim((string) ($row->disconnect_reason ?? ''));
    $bad = $reason !== '' && \Illuminate\Support\Str::contains(
        \Illuminate\Support\Str::lower($reason),
        ['timeout', 'auth', 'fail', 'error', 'lost', 'lcp', 'echo']
    );
@endphp
@if ($reason !== '')
    <span
        class="badge {{ $bad ? 'failed' : 'inactive' }}"
        style="white-space:nowrap"
        @if (! empty($row->disconnect_reason_at))
            title="Most recent drop — {{ \Illuminate\Support\Carbon::parse($row->disconnect_reason_at)->format('d/m/Y H:i') }}"
        @endif
    >{{ $reason }}</span>
@else
    <span class="muted">&mdash;</span>
@endif
