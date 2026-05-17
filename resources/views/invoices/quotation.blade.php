<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation - {{ $invoice->invoice_no }}</title>
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
        .doc-title h2 { font-size:30px; color:var(--brand-dark); letter-spacing:1px; }
        .doc-no { margin-top:8px; color:var(--muted); font-weight:700; }
        .meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px; }
        .box { border:1px solid var(--line); border-radius:6px; overflow:hidden; }
        .box h3 { background:var(--soft); border-bottom:1px solid var(--line); padding:8px 10px; font-size:13px; text-transform:uppercase; color:var(--brand-dark); }
        .box-body { padding:10px; line-height:1.7; }
        .muted { color:var(--muted); }
        .strong { font-weight:700; }
        .kv { display:grid; grid-template-columns:120px 1fr; column-gap:8px; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { border:1px solid var(--line); padding:9px 10px; vertical-align:top; }
        th { background:var(--brand); color:#fff; text-transform:uppercase; font-size:12px; }
        .center { text-align:center; }
        .right { text-align:right; }
        .summary { display:grid; grid-template-columns:1fr 74mm; gap:18px; margin-top:16px; align-items:start; }
        .notes, .amount-words { border:1px solid var(--line); border-radius:6px; padding:10px; line-height:1.6; }
        .totals { border:1px solid var(--line); border-radius:6px; overflow:hidden; }
        .total-row { display:grid; grid-template-columns:1fr auto; gap:12px; padding:9px 10px; border-bottom:1px solid var(--line); }
        .total-row:last-child { border-bottom:0; }
        .grand { background:var(--brand-dark); color:#fff; font-size:16px; font-weight:800; }
        .amount-words { margin-top:12px; }
        .signatures { display:grid; grid-template-columns:1fr 1fr; gap:42px; margin-top:32mm; }
        .signature-line { border-top:1px solid var(--ink); padding-top:8px; text-align:center; font-weight:700; }
        .no-sign-note { display:none; margin-top:28mm; border:1px solid var(--line); border-radius:6px; padding:14px; text-align:center; font-weight:700; line-height:1.7; background:var(--soft); color:var(--brand-dark); }
        body.no-signature .signatures { display:none; }
        body.no-signature .no-sign-note { display:block; }
        .footer { margin-top:18px; padding-top:10px; border-top:1px solid var(--line); text-align:center; color:var(--muted); font-size:12px; }
        @page { size:A4; margin:0; }
        @media print {
            body { background:#fff; }
            .toolbar { display:none; }
            .page { width:210mm; min-height:297mm; margin:0; box-shadow:none; }

            body.compact-print { font-size:10.5px; }
            body.compact-print .page { height:297mm; min-height:0; padding:8mm 9mm; overflow:hidden; }
            body.compact-print .brand-bar { gap:10px; border-bottom-width:2px; padding-bottom:6px; margin-bottom:7px; }
            body.compact-print .company h1 { font-size:20px; }
            body.compact-print .company p { margin-top:2px; line-height:1.2; }
            body.compact-print .doc-title h2 { font-size:22px; }
            body.compact-print .doc-no { margin-top:4px; }
            body.compact-print .meta-grid { gap:8px; margin-bottom:7px; }
            body.compact-print .box { border-radius:4px; }
            body.compact-print .box h3 { padding:4px 6px; font-size:10px; }
            body.compact-print .box-body { padding:5px 6px; line-height:1.25; }
            body.compact-print .kv { grid-template-columns:86px 1fr; column-gap:5px; }
            body.compact-print table { margin-top:6px; table-layout:fixed; }
            body.compact-print th,
            body.compact-print td { padding:3px 5px; line-height:1.12; }
            body.compact-print th { font-size:9px; letter-spacing:0; }
            body.compact-print tbody td:nth-child(2) { overflow-wrap:anywhere; }
            body.compact-print .summary { grid-template-columns:1fr 58mm; gap:8px; margin-top:7px; }
            body.compact-print .notes,
            body.compact-print .amount-words { padding:5px 6px; line-height:1.25; }
            body.compact-print .total-row { padding:4px 6px; gap:8px; }
            body.compact-print .grand { font-size:12px; }
            body.compact-print .amount-words { margin-top:6px; }
            body.compact-print .signatures { gap:24px; margin-top:10mm; }
            body.compact-print .signature-line { padding-top:5px; }
            body.compact-print .no-sign-note { margin-top:8mm; padding:7px; line-height:1.3; }
            body.compact-print .footer { margin-top:6px; padding-top:5px; font-size:9.5px; }

            body.dense-print { font-size:9.5px; }
            body.dense-print .page { padding:6mm 8mm; }
            body.dense-print .brand-bar { padding-bottom:4px; margin-bottom:5px; }
            body.dense-print .company h1 { font-size:18px; }
            body.dense-print .company p { line-height:1.12; }
            body.dense-print .doc-title h2 { font-size:20px; }
            body.dense-print .meta-grid { margin-bottom:5px; }
            body.dense-print .box h3 { padding:3px 5px; }
            body.dense-print .box-body { padding:4px 5px; line-height:1.18; }
            body.dense-print th,
            body.dense-print td { padding:2px 4px; line-height:1.05; }
            body.dense-print tbody td:nth-child(2) { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            body.dense-print .summary { margin-top:5px; }
            body.dense-print .notes,
            body.dense-print .amount-words { padding:4px 5px; line-height:1.15; }
            body.dense-print .total-row { padding:3px 5px; }
            body.dense-print .signatures { margin-top:6mm; }
            body.dense-print .footer { margin-top:4px; padding-top:4px; font-size:9px; }
        }
    </style>
</head>
<body class="{{ $invoice->items->count() >= 30 ? 'compact-print dense-print' : ($invoice->items->count() >= 25 ? 'compact-print' : '') }}">
    @php
        $numberToWords = function (int $number) use (&$numberToWords): string {
            $ones = [0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'];
            $tens = [2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'];
            if ($number < 20) return $ones[$number];
            if ($number < 100) return $tens[intdiv($number, 10)].($number % 10 ? ' '.$ones[$number % 10] : '');
            if ($number < 1000) return $ones[intdiv($number, 100)].' Hundred'.($number % 100 ? ' '.$numberToWords($number % 100) : '');
            foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $value => $label) {
                if ($number >= $value) return $numberToWords(intdiv($number, $value)).' '.$label.($number % $value ? ' '.$numberToWords($number % $value) : '');
            }
            return '';
        };
        $amountInWords = function (float $amount) use ($numberToWords): string {
            $taka = (int) floor($amount);
            $paisa = (int) round(($amount - $taka) * 100);
            return $numberToWords($taka).' Taka'.($paisa > 0 ? ' and '.$numberToWords($paisa).' Paisa' : '').' Only';
        };
    @endphp

    <div class="toolbar">
        <label class="print-option"><input type="checkbox" id="noSignatureOption"> Signature ছাড়াই print</label>
        <button onclick="window.print()" class="btn">Print Quotation</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn light">Back to Invoice</a>
    </div>

    <main class="page">
        <section class="brand-bar">
            <div class="company">
                <h1>Ultimate Solution</h1>
                <p>your ultimate IT partner<br>44/1 K Khan Road, Kushtia<br>Mobile - 01812707070, 01798987928<br>us.com.bd | info@us.com.bd</p>
            </div>
            <div class="doc-title">
                <h2>QUOTATION</h2>
                <div class="doc-no">Ref: {{ $invoice->invoice_no }}</div>
            </div>
        </section>

        <section class="meta-grid">
            <div class="box">
                <h3>Quotation For</h3>
                <div class="box-body">
                    <p class="strong">{{ $invoice->customer->name }}</p>
                    <p>{{ $invoice->customer->address }}</p>
                    <p>Phone: {{ $invoice->customer->phone }}</p>
                    <p>Connection ID: {{ $invoice->customer->connection_id }}</p>
                </div>
            </div>
            <div class="box">
                <h3>Quotation Details</h3>
                <div class="box-body">
                    <div class="kv"><span class="muted">Quotation No</span><span>QT-{{ $invoice->invoice_no }}</span></div>
                    <div class="kv"><span class="muted">Date</span><span>{{ now()->format('d M Y') }}</span></div>
                    <div class="kv"><span class="muted">Valid Until</span><span>{{ now()->addDays(15)->format('d M Y') }}</span></div>
                    <div class="kv"><span class="muted">Reference Month</span><span>{{ $invoice->formatted_billing_month }}</span></div>
                </div>
            </div>
        </section>

        <table>
            <thead><tr><th style="width:42px;">SL</th><th>Description</th><th style="width:72px;" class="center">Qty</th><th style="width:110px;" class="right">Rate</th><th style="width:120px;" class="right">Amount</th></tr></thead>
            <tbody>
                @forelse ($invoice->items as $index => $item)
                    <tr><td class="center">{{ $index + 1 }}</td><td>{{ $item->product_name }}</td><td class="center">{{ $item->quantity }}</td><td class="right">{{ number_format($item->unit_price, 2) }}</td><td class="right">{{ number_format($item->total, 2) }}</td></tr>
                @empty
                    <tr><td class="center">1</td><td>Monthly internet service for {{ $invoice->formatted_billing_month }}</td><td class="center">1</td><td class="right">{{ number_format($invoice->subtotal, 2) }}</td><td class="right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <section class="summary">
            <div class="notes">
                <p class="strong">Terms & Conditions</p>
                <p>This quotation is valid for 15 days. Delivery, installation and payment terms may vary based on final confirmation.</p>
            </div>
            <div class="totals">
                <div class="total-row"><span>Subtotal</span><span>{{ number_format($invoice->subtotal, 2) }}</span></div>
                @if ((float) $invoice->discount > 0)
                    <div class="total-row"><span>Discount</span><span>{{ number_format($invoice->discount, 2) }}</span></div>
                @endif
                @if ((float) ($invoice->vat ?? 0) > 0)
                    <div class="total-row"><span>VAT</span><span>{{ number_format($invoice->vat, 2) }}</span></div>
                @endif
                <div class="total-row grand"><span>Quoted Total</span><span>{{ number_format($invoice->total, 2) }}</span></div>
            </div>
        </section>

        <div class="amount-words"><span class="strong">Amount in Words:</span> {{ $amountInWords((float) $invoice->total) }}</div>

        <section class="signatures">
            <div class="signature-line">Customer Signature</div>
            <div class="signature-line">Authorized Signature</div>
        </section>
        <div class="no-sign-note">Computer-generated quotation<br>No signature required</div>
        <div class="footer">This is a computer-generated quotation. Thank you for choosing Ultimate Solution.</div>
    </main>

    <script>
        document.getElementById('noSignatureOption').addEventListener('change', function () {
            document.body.classList.toggle('no-signature', this.checked);
        });
    </script>
</body>
</html>
