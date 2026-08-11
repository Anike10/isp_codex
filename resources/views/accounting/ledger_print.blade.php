@php($printMode = request('print_mode') === 'color' ? 'color' : 'bw')
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $selectedCustomer ? 'Party Ledger' : 'Accounting Ledger' }} - {{ $selectedOrganization->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/page-help.css') }}?v=20260811-1">
    <style>
        :root { --ink:#172033; --muted:#667085; --line:#b9c4d2; --brand:#116149; --soft:#eef7f3; }
        * { box-sizing:border-box; }
        body { margin:0; background:#e9eef5; color:var(--ink); font-family:Arial, Helvetica, sans-serif; font-size:11px; }
        .toolbar { display:flex; align-items:end; justify-content:center; gap:10px; padding:14px; flex-wrap:wrap; }
        .toolbar form { display:flex; align-items:end; justify-content:center; gap:10px; flex-wrap:wrap; }
        .print-mode { display:inline-flex; align-items:center; gap:4px; min-height:36px; background:#fff; border:1px solid #c4ceda; border-radius:6px; padding:4px; font-weight:700; }
        .print-mode label { display:inline-flex; align-items:center; gap:6px; border-radius:4px; padding:6px 9px; cursor:pointer; }
        .print-mode input { accent-color:var(--brand); }
        .print-mode label:has(input:checked) { background:var(--brand); color:#fff; }
        .field { display:grid; gap:4px; color:#344054; font-size:11px; font-weight:700; }
        .field select, .field input { min-height:36px; padding:7px 9px; border:1px solid #c4ceda; border-radius:6px; background:#fff; color:var(--ink); }
        .field select { min-width:220px; }
        .btn { min-height:36px; border:0; border-radius:6px; background:var(--brand); color:#fff; padding:8px 14px; cursor:pointer; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
        .btn.light { background:#d9e2ed; color:var(--ink); }
        .report { width:210mm; min-height:297mm; margin:0 auto 20px; background:#fff; padding:10mm; box-shadow:0 10px 30px rgba(15, 23, 42, .16); }
        .report-header { display:grid; grid-template-columns:1fr auto; gap:18px; align-items:start; border-bottom:3px solid var(--brand); padding-bottom:10px; margin-bottom:10px; }
        .organization { display:flex; gap:10px; align-items:start; }
        .logo { max-width:70px; max-height:52px; object-fit:contain; }
        h1, h2, p { margin:0; }
        .organization h1 { color:#0b3f31; font-size:21px; }
        .organization p { margin-top:3px; color:var(--muted); line-height:1.4; }
        .report-title { text-align:right; }
        .report-title h2 { color:#0b3f31; font-size:20px; text-transform:uppercase; }
        .report-title p { margin-top:5px; color:var(--muted); line-height:1.5; }
        .summary { display:grid; grid-template-columns:1.5fr repeat(3, 1fr); border:1px solid var(--line); margin-bottom:10px; }
        .summary > div { padding:7px 9px; border-right:1px solid var(--line); }
        .summary > div:last-child { border-right:0; }
        .summary span { display:block; color:var(--muted); font-size:9px; text-transform:uppercase; font-weight:700; }
        .summary strong { display:block; margin-top:3px; font-size:12px; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        thead { display:table-header-group; }
        th, td { border:1px solid var(--line); padding:5px 5px; vertical-align:top; line-height:1.3; overflow-wrap:anywhere; }
        th { background:var(--soft); color:#0b3f31; text-align:left; font-size:9px; text-transform:uppercase; }
        tbody tr:nth-child(even) td { background:#f1f7f5; }
        tbody tr { break-inside:avoid; page-break-inside:avoid; }
        .number { text-align:right; white-space:nowrap; }
        .sl { width:5%; text-align:center; }
        .date { width:13%; }
        .type { width:9%; }
        .party { width:13%; }
        .note { width:24%; }
        .money { width:12%; }
        .party-ledger .sl { width:5%; }
        .party-ledger .date { width:14%; }
        .party-ledger .type { width:9%; }
        .party-ledger .note { width:36%; }
        .party-ledger .money { width:12%; }
        .totals { width:58%; margin:10px 0 0 auto; border:1px solid var(--line); break-inside:avoid; page-break-inside:avoid; }
        .total-row { display:grid; grid-template-columns:1fr 140px; }
        .total-row + .total-row { border-top:1px solid var(--line); }
        .total-row span, .total-row strong { padding:6px 8px; }
        .total-row span { background:var(--soft); color:#0b3f31; font-weight:700; }
        .total-row strong { border-left:1px solid var(--line); text-align:right; }
        .footer-note { margin-top:12px; padding-top:7px; border-top:1px solid var(--line); color:var(--muted); text-align:center; font-size:9px; }
        body.bw-print { --ink:#111; --muted:#414141; --line:#555; --brand:#111; --soft:#ececec; }
        body.bw-print .report-header { border-bottom-color:#111; }
        body.bw-print .organization h1,
        body.bw-print .report-title h2,
        body.bw-print .summary span,
        body.bw-print .total-row span { color:#111; }
        body.bw-print th { background:#222; color:#fff; }
        body.bw-print tbody tr:nth-child(even) td { background:#eeeeee; }
        @page { size:210mm 297mm; margin:9mm; }
        @media (max-width: 1050px) {
            .report { width:100%; margin:0; padding:14px; overflow:auto; }
            .report-header { grid-template-columns:1fr; }
            .report-title { text-align:left; }
        }
        @media print {
            body { background:#fff; }
            .toolbar { display:none !important; }
            .report { width:auto; min-height:0; margin:0; padding:0; overflow:visible; box-shadow:none; }
            .report-header { grid-template-columns:1fr auto; }
            .report-title { text-align:right; }
            table, th, td, .summary span, .total-row span { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            body.bw-print * { text-shadow:none !important; box-shadow:none !important; }
            body.bw-print th { background:#222 !important; color:#fff !important; }
            body.bw-print tbody tr:nth-child(even) td { background:#eeeeee !important; }
            body.bw-print th,
            body.bw-print td,
            body.bw-print .summary,
            body.bw-print .totals { border-color:#555 !important; }
            a { color:inherit; text-decoration:none; }
        }
    </style>
</head>
<body class="{{ $printMode === 'bw' ? 'bw-print' : 'color-print' }}">
<div class="toolbar">
    <form method="get" action="{{ route('accounting.ledger.print') }}">
        <div class="print-mode" aria-label="Print design">
            <label><input type="radio" name="print_mode" value="bw" @checked($printMode === 'bw')> Black &amp; white</label>
            <label><input type="radio" name="print_mode" value="color" @checked($printMode === 'color')> Color</label>
        </div>
        @if ($selectedCustomer)
            <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
        @endif
        <label class="field">
            <span>Organization</span>
            <select name="organization_id">
                @foreach ($organizations as $organizationOption)
                    <option value="{{ $organizationOption->id }}" @selected($selectedOrganization->is($organizationOption))>{{ $organizationOption->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="field">
            <span>From date</span>
            <input type="date" name="from" value="{{ request('from') }}">
        </label>
        <label class="field">
            <span>To date</span>
            <input type="date" name="to" value="{{ request('to') }}">
        </label>
        <button class="btn light" type="submit">Apply</button>
    </form>
    <button class="btn" type="button" onclick="window.print()">Print Report</button>
    @if (auth()->user()?->hasPermission('manage_invoices'))
        <a class="btn light" href="{{ route('organizations.index') }}">Organization Settings</a>
    @endif
    <a class="btn light" href="{{ route('accounting.ledger', array_filter([
        'customer_id' => $selectedCustomer?->id,
        'from' => request('from'),
        'to' => request('to'),
    ])) }}">Back to Ledger</a>
</div>

@include('partials.page_help', ['variant' => 'print'])

<main class="report">
    <header class="report-header">
        <div class="organization">
            @if ($selectedOrganization->logo_url)
                <img class="logo" src="{{ $selectedOrganization->logo_url }}" alt="{{ $selectedOrganization->name }} logo">
            @endif
            <div>
                <h1>{{ $selectedOrganization->name }}</h1>
                <p>
                    {!! nl2br(e($selectedOrganization->address ?: '')) !!}
                    @if ($selectedOrganization->mobile)<br>Mobile: {{ $selectedOrganization->mobile }}@endif
                    @if ($selectedOrganization->phone)<br>Phone: {{ $selectedOrganization->phone }}@endif
                    @if ($selectedOrganization->email)<br>Email: {{ $selectedOrganization->email }}@endif
                    @if ($selectedOrganization->website)<br>Web: {{ $selectedOrganization->website }}@endif
                    @if ($selectedOrganization->tax_id)<br>Tax/BIN: {{ $selectedOrganization->tax_id }}@endif
                </p>
            </div>
        </div>
        <div class="report-title">
            <h2>{{ $selectedCustomer ? 'Party Ledger' : 'Accounting Ledger' }}</h2>
            <p>
                @if ($selectedCustomer)Party: <strong>{{ $selectedCustomer->name }}</strong><br>@endif
                Period: <strong>{{ $from?->format('Y-m-d') ?: 'Beginning' }} to {{ $to?->format('Y-m-d') ?: 'Present' }}</strong><br>
                Generated: {{ now()->format('Y-m-d h:i:s A') }}
            </p>
        </div>
    </header>

    <section class="summary">
        <div><span>Report For</span><strong>{{ $selectedCustomer?->name ?: 'All Accounting Entries' }}</strong></div>
        <div><span>Total Debit</span><strong>BDT {{ number_format($totalDebit, 2) }}</strong></div>
        <div><span>Total Credit</span><strong>BDT {{ number_format($totalCredit, 2) }}</strong></div>
        <div><span>Net Balance</span><strong>BDT {{ number_format($totalDebit - $totalCredit, 2) }}</strong></div>
    </section>

    <table @class(['party-ledger' => $selectedCustomer])>
        <thead>
            <tr>
                <th class="sl">SL</th>
                <th class="date">Date</th>
                <th class="type">Type</th>
                @unless ($selectedCustomer)<th class="party">Party</th>@endunless
                <th class="note">Note</th>
                <th class="money number">Debit</th>
                <th class="money number">Credit</th>
                <th class="money number">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td class="sl">{{ $entry['serial'] }}</td>
                    <td>{{ $entry['date']?->format('Y-m-d') }}</td>
                    <td>{{ $entry['type'] }}</td>
                    @unless ($selectedCustomer)<td>{{ $entry['customer'] }}</td>@endunless
                    <td>{{ $entry['note'] }}</td>
                    <td class="number">{{ number_format($entry['debit'], 2) }}</td>
                    <td class="number">{{ number_format($entry['credit'], 2) }}</td>
                    <td class="number">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $selectedCustomer ? 7 : 8 }}" style="text-align:center;padding:16px">No ledger entries found for this date range.</td></tr>
            @endforelse
        </tbody>
    </table>

    <section class="totals">
        <div class="total-row"><span>Total Debit</span><strong>{{ number_format($totalDebit, 2) }}</strong></div>
        <div class="total-row"><span>Total Credit</span><strong>{{ number_format($totalCredit, 2) }}</strong></div>
        <div class="total-row"><span>Closing Balance</span><strong>{{ number_format($totalDebit - $totalCredit, 2) }}</strong></div>
    </section>

    @if ($selectedOrganization->footer_note)
        <div class="footer-note">{{ $selectedOrganization->footer_note }}</div>
    @endif
</main>
<script>
    document.querySelectorAll('input[name="print_mode"]').forEach(function (option) {
        option.addEventListener('change', function () {
            document.body.classList.toggle('bw-print', this.value === 'bw');
            document.body.classList.toggle('color-print', this.value === 'color');
        });
    });
</script>
</body>
</html>
