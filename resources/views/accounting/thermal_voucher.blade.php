<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thermal {{ $voucher['title'] }} - {{ $voucher['voucher_no'] }}</title>
    <style>
        :root { --paper-width:80mm; --ink:#111827; --muted:#4b5563; --line:#d1d5db; }
        * { box-sizing:border-box; }
        body { margin:0; background:#e5e7eb; color:var(--ink); font-family:Arial, Helvetica, sans-serif; font-size:12px; }
        body.paper-58 { --paper-width:58mm; }
        body.paper-72 { --paper-width:72mm; }
        body.paper-80 { --paper-width:80mm; }
        .toolbar { position:sticky; top:0; z-index:2; display:flex; justify-content:center; gap:8px; flex-wrap:wrap; padding:12px; background:#f8fafc; border-bottom:1px solid var(--line); }
        .btn, .paper-option { border:0; border-radius:6px; background:#116149; color:#fff; padding:9px 12px; cursor:pointer; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; min-height:36px; }
        .paper-group { display:flex; gap:4px; padding:3px; border:1px solid var(--line); border-radius:8px; background:#fff; }
        .paper-option { background:#eef2f7; color:var(--ink); min-width:50px; justify-content:center; }
        .paper-option.is-active { background:#116149; color:#fff; }
        .btn.light { background:#dfe7f1; color:var(--ink); }
        .preview-shell { padding:18px 0 28px; }
        .receipt { width:var(--paper-width); margin:0 auto; padding:4mm 3.5mm 5mm; background:#fff; box-shadow:0 12px 30px rgba(15, 23, 42, .18); overflow-wrap:anywhere; }
        .brand { text-align:center; }
        .brand h1 { margin:0; font-size:17px; line-height:1.2; text-transform:uppercase; }
        .brand p { margin:3px 0 0; color:var(--muted); line-height:1.35; }
        .title { margin-top:8px; padding:6px 0; border-top:1px dashed var(--ink); border-bottom:1px dashed var(--ink); text-align:center; font-weight:800; text-transform:uppercase; letter-spacing:0; }
        .row { display:grid; grid-template-columns:24mm minmax(0, 1fr); gap:5px; padding:3px 0; line-height:1.35; }
        .label { color:var(--muted); }
        .value { text-align:right; font-weight:700; }
        .amount { margin:8px 0; padding:7px 4px; border:1px solid var(--ink); text-align:center; }
        .amount span { display:block; font-size:10px; color:var(--muted); text-transform:uppercase; }
        .amount strong { display:block; margin-top:2px; font-size:18px; }
        .section { padding:6px 0; border-bottom:1px dashed var(--line); }
        .note { padding-top:6px; line-height:1.4; }
        .note-title { font-weight:800; margin-bottom:2px; }
        .powered-by { margin-top:8px; padding-top:6px; border-top:1px dashed var(--line); text-align:center; color:var(--muted); }
        .allocation-row { display:grid; grid-template-columns:minmax(0, 1fr) 21mm; gap:4px; padding:3px 0; line-height:1.35; }
        .allocation-row .amount-value { text-align:right; font-weight:700; }
        body.paper-58 .receipt { padding-left:2.4mm; padding-right:2.4mm; font-size:11px; }
        body.paper-58 .brand h1 { font-size:15px; }
        body.paper-58 .row { grid-template-columns:20mm minmax(0, 1fr); gap:3px; }
        body.paper-58 .amount strong { font-size:16px; }
        body.paper-72 .receipt { font-size:11.5px; }
        @media (max-width: 520px) {
            .toolbar { justify-content:flex-start; }
            .toolbar .btn { flex:1 1 130px; justify-content:center; }
            .paper-group { width:100%; }
            .paper-option { flex:1; }
            .preview-shell { overflow-x:auto; padding-left:10px; padding-right:10px; }
        }
        @media print {
            @page { margin:0; }
            body { width:var(--paper-width); margin:0; background:#fff; }
            .toolbar { display:none; }
            .preview-shell { padding:0; }
            .receipt { width:var(--paper-width); margin:0; padding-top:3mm; box-shadow:none; }
        }
    </style>
</head>
<body class="paper-80">
<div class="toolbar">
    <div class="paper-group" role="group" aria-label="Thermal paper width">
        <button class="paper-option" type="button" data-paper="58">58mm</button>
        <button class="paper-option" type="button" data-paper="72">72mm</button>
        <button class="paper-option" type="button" data-paper="80">80mm</button>
    </div>
    <button class="btn" type="button" onclick="window.print()">Print Thermal</button>
    <a class="btn light" href="{{ $voucher['back_url'] }}">Back</a>
</div>

<main class="preview-shell">
    <section class="receipt">
        <header class="brand">
            <h1>Kushtia Municipality</h1>
            <p>Kushtia<br>Mobile - +8801722323870<br>{{ now()->format('Y-m-d H:i') }}</p>
        </header>

        <div class="title">{{ $voucher['title'] }}</div>

        <div class="section">
            <div class="row"><span class="label">Voucher</span><span class="value">{{ $voucher['voucher_no'] }}</span></div>
            <div class="row"><span class="label">Date</span><span class="value">{{ $voucher['date']?->format('Y-m-d') }}</span></div>
        </div>

        <div class="amount">
            <span>Received Amount</span>
            <strong>BDT {{ number_format($voucher['amount'], 2) }}</strong>
        </div>

        <div class="section">
            <div class="row"><span class="label">{{ $voucher['paid_to_label'] }}</span><span class="value">{{ $voucher['paid_to'] }}</span></div>
            <div class="row"><span class="label">Bill Month</span><span class="value">{{ $voucher['bill_month'] }}</span></div>
        </div>

        @if (! empty($voucher['allocations']))
            <div class="section">
                <div class="note-title">Invoice Allocation</div>
                @foreach ($voucher['allocations'] as $allocation)
                    <div class="allocation-row">
                        <span>{{ $allocation['invoice_no'] }} {{ $allocation['bill_month'] ? '('.$allocation['bill_month'].')' : '' }}</span>
                        <span class="amount-value">{{ number_format($allocation['amount'], 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="note">
            <div class="note-title">Note</div>
            <div>{{ $voucher['note'] }}</div>
        </div>

        <div class="powered-by">Powered by Ultimate Solution</div>

    </section>
</main>

<script>
    const paperButtons = document.querySelectorAll('[data-paper]');
    const storageKey = 'paymentThermalPaperWidth';

    function setPaperWidth(width) {
        document.body.classList.remove('paper-58', 'paper-72', 'paper-80');
        document.body.classList.add(`paper-${width}`);
        paperButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.paper === width);
        });
        localStorage.setItem(storageKey, width);
    }

    paperButtons.forEach((button) => {
        button.addEventListener('click', () => setPaperWidth(button.dataset.paper));
    });

    setPaperWidth(localStorage.getItem(storageKey) || '80');
</script>
</body>
</html>
