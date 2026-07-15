@php
    $isStandaloneQuotation = $invoice instanceof \App\Models\Quotation;
    $quotationNumber = $isStandaloneQuotation ? $invoice->quotation_no : 'QT-'.$invoice->invoice_no;
    $quotationDate = $isStandaloneQuotation ? $invoice->quotation_date : now();
    $validUntil = $isStandaloneQuotation ? $invoice->valid_until : now()->addDays(15);
    $backUrl = $isStandaloneQuotation ? route('quotations.show', $invoice) : route('invoices.show', $invoice);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation - {{ $quotationNumber }}</title>
    <style>
        :root { --ink:#172033; --muted:#667085; --line:#cfd7e3; --brand:#116149; --brand-dark:#0b3f31; --soft:#eef7f3; }
        * { box-sizing:border-box; }
        body { margin:0; background:#eef2f7; color:var(--ink); font-family:Arial, Helvetica, sans-serif; font-size:13px; }
        .toolbar { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; gap:10px; padding:16px; }
        .btn { border:0; border-radius:6px; background:var(--brand); color:#fff; padding:10px 14px; cursor:pointer; font-weight:700; text-decoration:none; }
        .btn.light { background:#dfe7f1; color:var(--ink); }
        .print-option { display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--line); border-radius:6px; padding:9px 12px; font-weight:700; }
        .print-option input { width:16px; height:16px; }
        .print-mode { display:inline-flex; align-items:center; gap:4px; background:#fff; border:1px solid var(--line); border-radius:6px; padding:4px; font-weight:700; }
        .print-mode label { display:inline-flex; align-items:center; gap:6px; border-radius:4px; padding:6px 9px; cursor:pointer; }
        .print-mode input { accent-color:var(--brand); }
        .print-mode label:has(input:checked) { background:var(--brand); color:#fff; }
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
        tbody tr:nth-child(even) td { background:#f4f7fb; }
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
        body.bw-print { --ink:#111; --muted:#414141; --line:#555; --brand:#111; --brand-dark:#111; --soft:#f3f3f3; }
        body.bw-print .brand-bar { border-bottom:2px solid #111; }
        body.bw-print .company h1,
        body.bw-print .doc-title h2,
        body.bw-print .box h3,
        body.bw-print .no-sign-note { color:#111; }
        body.bw-print .box h3,
        body.bw-print tbody tr:nth-child(even) td,
        body.bw-print .no-sign-note { background:#f3f3f3; }
        body.bw-print th,
        body.bw-print .grand { background:#222; color:#fff; }
        .footer { margin-top:18px; padding-top:10px; border-top:1px solid var(--line); text-align:center; color:var(--muted); font-size:12px; }
        @page { size:A4; margin:0; }
        @media print {
            body { background:#fff; }
            table, th, td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .toolbar { display:none; }
            body.bw-print * { text-shadow:none !important; box-shadow:none !important; }
            body.bw-print th,
            body.bw-print .grand { background:#222 !important; color:#fff !important; }
            body.bw-print .box h3,
            body.bw-print tbody tr:nth-child(even) td,
            body.bw-print .notes,
            body.bw-print .amount-words,
            body.bw-print .no-sign-note { background:#f4f4f4 !important; color:#111 !important; }
            body.bw-print .brand-bar,
            body.bw-print .box,
            body.bw-print .notes,
            body.bw-print .totals,
            body.bw-print .amount-words,
            body.bw-print th,
            body.bw-print td { border-color:#555 !important; }
            .page { width:210mm; min-height:287mm; margin:0; padding:12mm 14mm 10mm; box-shadow:none; page-break-after:avoid; break-after:avoid; }
            .signatures { margin-top:20mm; }
            .no-sign-note { margin-top:17mm; }
            .footer { margin-top:10px; padding-top:6px; }

            body.compact-print { font-size:10.5px; }
            body.compact-print .page { height:287mm; min-height:0; padding:7mm 9mm 6mm; overflow:hidden; }
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
<body class="bw-print {{ $selectedOrganization->default_without_signature ? 'no-signature' : '' }} {{ $invoice->items->count() >= 30 ? 'compact-print dense-print' : ($invoice->items->count() >= 25 ? 'compact-print' : '') }}">
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
        <div class="print-mode" aria-label="Print design">
            <label><input type="radio" name="print_mode" value="bw" checked> Black & white</label>
            <label><input type="radio" name="print_mode" value="color"> Color</label>
        </div>
        <label class="print-option"><input type="checkbox" id="noSignatureOption" @checked($selectedOrganization->default_without_signature)> Print without signature</label>
        @include('partials.organization_print_selector')
        <button onclick="recordPrint('{{ $isStandaloneQuotation ? 'quotation' : 'invoice_quotation' }}', {{ $invoice->id }})" class="btn">Print Quotation</button>
        <a href="{{ $backUrl }}" class="btn light">Back to {{ $isStandaloneQuotation ? 'Quotation' : 'Invoice' }}</a>
    </div>

    <main class="page">
        <section class="brand-bar">
            <div class="company">
                @if($selectedOrganization->logo_url)<img src="{{ $selectedOrganization->logo_url }}" alt="{{ $selectedOrganization->name }} logo" style="max-width:90px;max-height:52px;margin-bottom:6px">@endif
                <h1>{{ $selectedOrganization->name }}</h1>
                <p>{!! nl2br(e($selectedOrganization->address ?: '')) !!}@if($selectedOrganization->mobile)<br>Mobile - {{ $selectedOrganization->mobile }}@endif @if($selectedOrganization->phone)<br>Phone - {{ $selectedOrganization->phone }}@endif @if($selectedOrganization->email)<br>{{ $selectedOrganization->email }}@endif @if($selectedOrganization->website)<br>{{ $selectedOrganization->website }}@endif @if($selectedOrganization->tax_id)<br>Tax/BIN - {{ $selectedOrganization->tax_id }}@endif</p>
            </div>
            <div class="doc-title">
                <h2>QUOTATION</h2>
                <div class="doc-no">Ref: {{ $quotationNumber }}</div>
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
                    <div class="kv"><span class="muted">Quotation No</span><span>{{ $quotationNumber }}</span></div>
                    <div class="kv"><span class="muted">Date</span><span>{{ $quotationDate?->format('d M Y') }}</span></div>
                    <div class="kv"><span class="muted">Valid Until</span><span>{{ $validUntil?->format('d M Y') ?? 'Open' }}</span></div>
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
                <p style="white-space:pre-line">{{ $isStandaloneQuotation && filled($invoice->payment_note) ? $invoice->payment_note : 'This quotation is valid for 15 days. Delivery, installation and payment terms may vary based on final confirmation.' }}</p>
                @if ($invoice->show_public_note && filled($invoice->public_note))
                    <p class="strong" style="margin-top:8px;">Quotation Note</p>
                    <p style="white-space:pre-line">{{ $invoice->public_note }}</p>
                @endif
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
            <div class="signature-line">Party Signature</div>
            <div class="signature-line">Authorized Signature</div>
        </section>
        <div class="no-sign-note">Computer-generated quotation<br>No signature required</div>
        <div class="footer">Powered by Ultimate Solution</div>
    </main>

    <script>
        document.getElementById('noSignatureOption').addEventListener('change', function () {
            document.body.classList.toggle('no-signature', this.checked);
        });

        document.querySelectorAll('input[name="print_mode"]').forEach((input) => {
            input.addEventListener('change', function () {
                document.body.classList.toggle('bw-print', this.value === 'bw');
            });
        });
    </script>
    @include('partials.print_audit_script')
</body>
</html>
