<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $voucher['title'] }} - {{ $voucher['voucher_no'] }}</title>
    <style>
        :root { --ink:#172033; --muted:#667085; --line:#cfd7e3; --brand:#116149; --soft:#eef7f3; }
        * { box-sizing:border-box; }
        body { margin:0; background:#eef2f7; color:var(--ink); font-family:Arial, Helvetica, sans-serif; font-size:13px; }
        .toolbar { display:flex; justify-content:center; gap:10px; padding:16px; flex-wrap:wrap; }
        .btn { border:0; border-radius:6px; background:var(--brand); color:#fff; padding:10px 14px; cursor:pointer; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; }
        .btn.light { background:#dfe7f1; color:var(--ink); }
        .page { width:148mm; min-height:210mm; margin:0 auto 24px; background:#fff; padding:16mm; box-shadow:0 10px 30px rgba(15, 23, 42, .18); }
        .brand-bar { display:grid; grid-template-columns:1fr auto; gap:18px; align-items:start; border-bottom:4px solid var(--brand); padding-bottom:14px; margin-bottom:18px; }
        h1, h2, h3, p { margin:0; }
        .company h1 { font-size:24px; color:#0b3f31; }
        .company p { margin-top:4px; color:var(--muted); line-height:1.45; }
        .voucher-title { text-align:right; }
        .voucher-title h2 { font-size:24px; color:#0b3f31; text-transform:uppercase; }
        .badge { display:inline-block; margin-top:8px; padding:5px 10px; border:1px solid var(--brand); border-radius:999px; color:var(--brand); font-weight:800; }
        .amount-box { margin:18px 0; padding:18px; border:2px solid var(--brand); border-radius:8px; background:var(--soft); text-align:center; }
        .amount-box span { display:block; color:var(--muted); font-weight:700; text-transform:uppercase; }
        .amount-box strong { display:block; margin-top:8px; color:var(--brand); font-size:34px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .box { border:1px solid var(--line); border-radius:6px; overflow:hidden; }
        .box h3 { background:var(--soft); border-bottom:1px solid var(--line); padding:8px 10px; font-size:12px; text-transform:uppercase; color:#0b3f31; }
        .box-body { padding:10px; line-height:1.75; }
        .box-body .kv:nth-child(even) { background:#f4f7fb; }
        .kv { display:grid; grid-template-columns:105px 1fr; column-gap:8px; }
        .muted { color:var(--muted); }
        .strong { font-weight:700; }
        .note { margin-top:12px; border:1px solid var(--line); border-radius:6px; padding:10px; min-height:70px; line-height:1.6; }
        .allocation-table { width:100%; border-collapse:collapse; margin-top:12px; }
        .allocation-table th, .allocation-table td { border:1px solid var(--line); padding:7px 8px; text-align:left; }
        .allocation-table th { background:var(--soft); color:#0b3f31; font-size:12px; text-transform:uppercase; }
        .allocation-table td:last-child, .allocation-table th:last-child { text-align:right; }
        .signatures { display:grid; grid-template-columns:1fr 1fr; gap:36px; margin-top:42mm; }
        .signature { border-top:1px solid var(--ink); padding-top:8px; text-align:center; font-weight:700; }
        @media print {
            body { background:#fff; }
            .box h3, .box-body .kv, .amount-box {
                -webkit-print-color-adjust:exact;
                print-color-adjust:exact;
            }
            .toolbar { display:none; }
            .page { width:auto; min-height:auto; margin:0; padding:10mm; box-shadow:none; }
            @page { size:A5 portrait; margin:8mm; }
        }
        @media (max-width: 680px) {
            .page { width:auto; min-height:auto; margin:0; padding:14px; }
            .brand-bar, .grid { grid-template-columns:1fr; }
            .voucher-title { text-align:left; }
            .amount-box strong { font-size:28px; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button class="btn" type="button" onclick="window.print()">Print Voucher</button>
    <a class="btn light" href="{{ $voucher['back_url'] }}">Back</a>
</div>

<main class="page">
    <div class="brand-bar">
        <div class="company">
            <h1>Kushtia Municipality</h1>
            <p>Kushtia<br>Mobile - +8801722323870<br>Generated: {{ now()->format('Y-m-d H:i') }}</p>
        </div>
        <div class="voucher-title">
            <h2>{{ $voucher['title'] }}</h2>
            <span class="badge">{{ $voucher['voucher_no'] }}</span>
        </div>
    </div>

    <div class="amount-box">
        <span>Voucher Amount</span>
        <strong>BDT {{ number_format($voucher['amount'], 2) }}</strong>
    </div>

    <div class="grid">
        <section class="box">
            <h3>Voucher Details</h3>
            <div class="box-body">
                <div class="kv"><span class="muted">Date</span><span class="strong">{{ $voucher['date']?->format('Y-m-d') }}</span></div>
                <div class="kv"><span class="muted">Type</span><span class="strong">{{ $voucher['type'] }}</span></div>
                <div class="kv"><span class="muted">Reference</span><span class="strong">{{ $voucher['reference'] }}</span></div>
            </div>
        </section>

        <section class="box">
            <h3>Payment Details</h3>
            <div class="box-body">
                <div class="kv"><span class="muted">Method</span><span class="strong">{{ $voucher['method'] }}</span></div>
                <div class="kv"><span class="muted">Account</span><span class="strong">{{ $voucher['account'] }}</span></div>
                <div class="kv"><span class="muted">Amount</span><span class="strong">BDT {{ number_format($voucher['amount'], 2) }}</span></div>
            </div>
        </section>

        <section class="box">
            <h3>Party</h3>
            <div class="box-body">
                <div class="kv"><span class="muted">{{ $voucher['paid_to_label'] }}</span><span class="strong">{{ $voucher['paid_to'] }}</span></div>
                <div class="kv"><span class="muted">{{ $voucher['secondary_label'] }}</span><span class="strong">{{ $voucher['secondary_value'] }}</span></div>
                @if (! empty($voucher['bill_month']))
                    <div class="kv"><span class="muted">Bill Month</span><span class="strong">{{ $voucher['bill_month'] }}</span></div>
                @endif
            </div>
        </section>

        <section class="box">
            <h3>Prepared By</h3>
            <div class="box-body">
                <div class="kv"><span class="muted">System</span><span class="strong">Kushtia Municipality</span></div>
                <div class="kv"><span class="muted">Printed</span><span class="strong">{{ now()->format('Y-m-d H:i') }}</span></div>
            </div>
        </section>
    </div>

    <div class="note">
        <div class="strong">Note</div>
        <div>{{ $voucher['note'] }}</div>
    </div>

    @if (! empty($voucher['allocations']))
        <table class="allocation-table">
            <thead>
                <tr><th>Invoice</th><th>Bill Month</th><th>Allocated Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($voucher['allocations'] as $allocation)
                    <tr>
                        <td>
                            @if (! empty($allocation['url']))
                                <a href="{{ $allocation['url'] }}">{{ $allocation['invoice_no'] }}</a>
                            @else
                                {{ $allocation['invoice_no'] }}
                            @endif
                        </td>
                        <td>{{ $allocation['bill_month'] }}</td>
                        <td>BDT {{ number_format($allocation['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="signatures">
        <div class="signature">Prepared By</div>
        <div class="signature">Received / Approved By</div>
    </div>
</main>
</body>
</html>
