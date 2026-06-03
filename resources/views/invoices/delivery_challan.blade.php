<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery Challan - {{ $invoice->invoice_no }}</title>
    <style>
        :root { --ink:#172033; --muted:#667085; --line:#cfd7e3; --brand:#116149; --brand-dark:#0b3f31; --soft:#eef7f3; }
        * { box-sizing:border-box; }
        body { margin:0; background:#eef2f7; color:var(--ink); font-family:Arial, Helvetica, sans-serif; font-size:13px; }
        .toolbar { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:10px; padding:16px; }
        .btn { border:0; border-radius:6px; background:var(--brand); color:#fff; padding:10px 14px; cursor:pointer; font-weight:700; text-decoration:none; }
        .btn.light { background:#dfe7f1; color:var(--ink); }
        .print-option { display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--line); border-radius:6px; padding:9px 12px; font-weight:700; }
        .print-option input { width:16px; height:16px; }
        .page { width:210mm; min-height:297mm; margin:0 auto 24px; background:#fff; padding:18mm; box-shadow:0 10px 30px rgba(15,23,42,.18); }
        .brand-bar { display:grid; grid-template-columns:1fr auto; gap:18px; align-items:start; border-bottom:4px solid var(--brand); padding-bottom:16px; margin-bottom:18px; }
        h1, h2, h3, p { margin:0; }
        .company h1 { font-size:28px; color:var(--brand-dark); }
        .company p { margin-top:4px; color:var(--muted); line-height:1.45; }
        .doc-title { text-align:right; }
        .doc-title h2 { font-size:29px; color:var(--brand-dark); letter-spacing:1px; }
        .doc-no { margin-top:8px; color:var(--muted); font-weight:700; }
        .meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px; }
        .box { border:1px solid var(--line); border-radius:6px; overflow:hidden; }
        .box h3 { background:var(--soft); border-bottom:1px solid var(--line); padding:8px 10px; font-size:13px; text-transform:uppercase; color:var(--brand-dark); }
        .box-body { padding:10px; line-height:1.7; }
        .muted { color:var(--muted); }
        .strong { font-weight:700; }
        .kv { display:grid; grid-template-columns:120px 1fr; column-gap:8px; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { border:1px solid var(--line); padding:10px; vertical-align:top; }
        th { background:var(--brand); color:#fff; text-transform:uppercase; font-size:12px; }
        tbody tr:nth-child(even) td { background:#f4f7fb; }
        .center { text-align:center; }
        .notes { margin-top:16px; border:1px solid var(--line); border-radius:6px; padding:10px; line-height:1.7; min-height:76px; }
        .signatures { display:grid; grid-template-columns:1fr 1fr 1fr; gap:28px; margin-top:42mm; }
        .signature-line { border-top:1px solid var(--ink); padding-top:8px; text-align:center; font-weight:700; }
        .no-sign-note { display:none; margin-top:36mm; border:1px solid var(--line); border-radius:6px; padding:14px; text-align:center; font-weight:700; line-height:1.7; background:var(--soft); color:var(--brand-dark); }
        body.no-signature .signatures { display:none; }
        body.no-signature .no-sign-note { display:block; }
        .footer { margin-top:18px; padding-top:10px; border-top:1px solid var(--line); text-align:center; color:var(--muted); font-size:12px; }
        @page { size:A4; margin:0; }
        @media print {
            body { background:#fff; }
            table, th, td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .toolbar { display:none; }
            .page { width:210mm; min-height:287mm; margin:0; padding:12mm 14mm 10mm; box-shadow:none; page-break-after:avoid; break-after:avoid; }
            .signatures { margin-top:22mm; }
            .no-sign-note { margin-top:18mm; }
            .footer { margin-top:10px; padding-top:6px; }

            body.compact-print { font-size:10.5px; }
            body.compact-print .page { height:287mm; min-height:0; padding:7mm 9mm 6mm; overflow:hidden; }
            body.compact-print .brand-bar { gap:10px; border-bottom-width:2px; padding-bottom:6px; margin-bottom:7px; }
            body.compact-print .company h1 { font-size:20px; }
            body.compact-print .company p { margin-top:2px; line-height:1.2; }
            body.compact-print .doc-title h2 { font-size:21px; }
            body.compact-print .doc-no { margin-top:4px; }
            body.compact-print .meta-grid { gap:8px; margin-bottom:7px; }
            body.compact-print .box { border-radius:4px; }
            body.compact-print .box h3 { padding:4px 6px; font-size:10px; }
            body.compact-print .box-body { padding:5px 6px; line-height:1.25; }
            body.compact-print .kv { grid-template-columns:82px 1fr; column-gap:5px; }
            body.compact-print table { margin-top:6px; table-layout:fixed; }
            body.compact-print th,
            body.compact-print td { padding:3px 5px; line-height:1.12; }
            body.compact-print th { font-size:9px; letter-spacing:0; }
            body.compact-print tbody td:nth-child(2) { overflow-wrap:anywhere; }
            body.compact-print .notes { margin-top:7px; min-height:0; padding:5px 6px; line-height:1.25; }
            body.compact-print .signatures { gap:22px; margin-top:10mm; }
            body.compact-print .signature-line { padding-top:5px; }
            body.compact-print .no-sign-note { margin-top:8mm; padding:7px; line-height:1.3; }
            body.compact-print .footer { margin-top:6px; padding-top:5px; font-size:9.5px; }

            body.dense-print { font-size:9.5px; }
            body.dense-print .page { padding:6mm 8mm; }
            body.dense-print .brand-bar { padding-bottom:4px; margin-bottom:5px; }
            body.dense-print .company h1 { font-size:18px; }
            body.dense-print .company p { line-height:1.12; }
            body.dense-print .doc-title h2 { font-size:19px; }
            body.dense-print .meta-grid { margin-bottom:5px; }
            body.dense-print .box h3 { padding:3px 5px; }
            body.dense-print .box-body { padding:4px 5px; line-height:1.18; }
            body.dense-print th,
            body.dense-print td { padding:2px 4px; line-height:1.05; }
            body.dense-print tbody td:nth-child(2) { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            body.dense-print .notes { margin-top:5px; padding:4px 5px; line-height:1.15; }
            body.dense-print .signatures { margin-top:6mm; }
            body.dense-print .footer { margin-top:4px; padding-top:4px; font-size:9px; }
        }
    </style>
</head>
<body class="{{ $invoice->items->count() >= 30 ? 'compact-print dense-print' : ($invoice->items->count() >= 25 ? 'compact-print' : '') }}">
    <div class="toolbar">
        <label class="print-option"><input type="checkbox" id="noSignatureOption"> Signature ছাড়াই print</label>
        <button onclick="window.print()" class="btn">Print Challan</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn light">Back to Invoice</a>
    </div>

    <main class="page">
        <section class="brand-bar">
            <div class="company">
                <h1>Ultimate Solution</h1>
                <p>your ultimate IT partner<br>44/1 K Khan Road, Kushtia<br>Mobile - 01812707070, 01798987928<br>us.com.bd | info@us.com.bd</p>
            </div>
            <div class="doc-title">
                <h2>DELIVERY CHALLAN</h2>
                <div class="doc-no">Ref: {{ $invoice->invoice_no }}</div>
            </div>
        </section>

        <section class="meta-grid">
            <div class="box">
                <h3>Delivered To</h3>
                <div class="box-body">
                    <p class="strong">{{ $invoice->customer->name }}</p>
                    <p>{{ $invoice->customer->address }}</p>
                    <p>Phone: {{ $invoice->customer->phone }}</p>
                    <p>Connection ID: {{ $invoice->customer->connection_id }}</p>
                </div>
            </div>
            <div class="box">
                <h3>Challan Details</h3>
                <div class="box-body">
                    <div class="kv"><span class="muted">Challan No</span><span>DC-{{ $invoice->invoice_no }}</span></div>
                    <div class="kv"><span class="muted">Invoice Ref</span><span>{{ $invoice->invoice_no }}</span></div>
                    <div class="kv"><span class="muted">Date</span><span>{{ now()->format('d M Y') }}</span></div>
                    <div class="kv"><span class="muted">Bill Month</span><span>{{ $invoice->formatted_billing_month }}</span></div>
                </div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width:46px;">SL</th>
                    <th>Description</th>
                    <th style="width:90px;" class="center">Qty</th>
                    <th style="width:170px;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoice->items as $index => $item)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td class="center">1</td>
                        <td>Monthly internet service for {{ $invoice->formatted_billing_month }}</td>
                        <td class="center">1</td>
                        <td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="notes">
            <p class="strong">Delivery Note</p>
            <p>The above goods/services have been delivered to the party in good condition.</p>
        </div>

        <section class="signatures">
            <div class="signature-line">Prepared By</div>
            <div class="signature-line">Delivered By</div>
            <div class="signature-line">Received By</div>
        </section>
        <div class="no-sign-note">Computer-generated delivery challan<br>No signature required</div>
        <div class="footer">This is a computer-generated delivery challan. Thank you for choosing Ultimate Solution.</div>
    </main>

    <script>
        document.getElementById('noSignatureOption').addEventListener('change', function () {
            document.body.classList.toggle('no-signature', this.checked);
        });
    </script>
</body>
</html>
