<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice - {{ $invoice->invoice_no }}</title>
    <style>
        :root {
            --ink: #172033;
            --muted: #667085;
            --line: #cfd7e3;
            --brand: #116149;
            --brand-dark: #0b3f31;
            --soft: #eef7f3;
            --danger: #b42318;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef2f7;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
        }

        .toolbar {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .print-option {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 9px 12px;
            font-weight: 700;
        }

        .print-option input {
            width: 16px;
            height: 16px;
        }

        .print-mode {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 4px;
            font-weight: 700;
        }

        .print-mode label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 4px;
            padding: 6px 9px;
            cursor: pointer;
        }

        .print-mode input { accent-color: var(--brand); }
        .print-mode label:has(input:checked) {
            background: var(--brand);
            color: #fff;
        }

        .btn {
            border: 0;
            border-radius: 6px;
            background: var(--brand);
            color: #fff;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
        }

        .btn.light {
            background: #dfe7f1;
            color: var(--ink);
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 24px;
            background: #fff;
            padding: 18mm;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .18);
        }

        .brand-bar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 18px;
            align-items: start;
            border-bottom: 4px solid var(--brand);
            padding-bottom: 16px;
            margin-bottom: 18px;
        }

        .company { display: block; }

        h1, h2, h3, p { margin: 0; }
        .company h1 { font-size: 28px; letter-spacing: .5px; color: var(--brand-dark); }
        .company p { margin-top: 4px; color: var(--muted); line-height: 1.45; }

        .bill-title {
            text-align: right;
        }

        .bill-title h2 {
            font-size: 30px;
            color: var(--brand-dark);
            letter-spacing: 1px;
        }

        .status {
            display: inline-block;
            margin-top: 8px;
            padding: 6px 12px;
            border: 2px solid currentColor;
            border-radius: 4px;
            color: var(--danger);
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .status.paid { color: var(--brand); }
        .status.partial { color: #b45309; }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .box {
            border: 1px solid var(--line);
            border-radius: 6px;
            overflow: hidden;
        }

        .box h3 {
            background: var(--soft);
            border-bottom: 1px solid var(--line);
            padding: 8px 10px;
            font-size: 13px;
            text-transform: uppercase;
            color: var(--brand-dark);
        }

        .box-body { padding: 10px; line-height: 1.7; }
        .muted { color: var(--muted); }
        .strong { font-weight: 700; }
        .item-serials {
            margin-top: 3px;
            color: var(--muted);
            font-size: .9em;
            overflow-wrap: anywhere;
        }

        .kv {
            display: grid;
            grid-template-columns: 110px 1fr;
            column-gap: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th, td {
            border: 1px solid var(--line);
            padding: 9px 10px;
            vertical-align: top;
        }

        th {
            background: var(--brand);
            color: #fff;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: .3px;
        }

        tbody tr:nth-child(even) td {
            background: #f4f7fb;
        }

        .center { text-align: center; }
        .right { text-align: right; }

        .summary {
            display: grid;
            grid-template-columns: 1fr 74mm;
            gap: 18px;
            margin-top: 16px;
            align-items: start;
        }

        .notes {
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 10px;
            min-height: 86px;
            line-height: 1.6;
        }

        .totals {
            border: 1px solid var(--line);
            border-radius: 6px;
            overflow: hidden;
        }

        .total-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            padding: 9px 10px;
            border-bottom: 1px solid var(--line);
        }

        .total-row:last-child { border-bottom: 0; }
        .grand {
            background: var(--brand-dark);
            color: #fff;
            font-size: 16px;
            font-weight: 800;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 42px;
            margin-top: 38mm;
        }

        .no-sign-note {
            display: none;
            margin-top: 32mm;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 14px;
            text-align: center;
            font-weight: 700;
            line-height: 1.7;
            background: var(--soft);
            color: var(--brand-dark);
        }

        .signature-line {
            border-top: 1px solid var(--ink);
            padding-top: 8px;
            text-align: center;
            font-weight: 700;
        }

        .footer {
            margin-top: 18px;
            padding-top: 10px;
            border-top: 1px solid var(--line);
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        .amount-words {
            margin-top: 12px;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 10px;
            line-height: 1.6;
        }

        body.no-signature .signatures { display: none; }
        body.no-signature .no-sign-note { display: block; }

        body.bw-print {
            --ink: #111;
            --muted: #414141;
            --line: #555;
            --brand: #111;
            --brand-dark: #111;
            --soft: #f3f3f3;
            --danger: #111;
        }

        body.bw-print .brand-bar { border-bottom: 2px solid #111; }
        body.bw-print .company h1,
        body.bw-print .bill-title h2,
        body.bw-print .box h3,
        body.bw-print .status,
        body.bw-print .no-sign-note { color: #111; }
        body.bw-print .box h3,
        body.bw-print tbody tr:nth-child(even) td,
        body.bw-print .no-sign-note { background: #f3f3f3; }
        body.bw-print th,
        body.bw-print .grand { background: #222; color: #fff; }
        body.bw-print .status { border-color: #111; background: #fff; }

        @page { size: A4; margin: 0; }

        @media print {
            body { background: #fff; }
            table, th, td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .toolbar { display: none; }
            body.bw-print * {
                text-shadow: none !important;
                box-shadow: none !important;
            }
            body.bw-print th,
            body.bw-print .grand {
                background: #222 !important;
                color: #fff !important;
            }
            body.bw-print .box h3,
            body.bw-print tbody tr:nth-child(even) td,
            body.bw-print .notes,
            body.bw-print .amount-words,
            body.bw-print .no-sign-note {
                background: #f4f4f4 !important;
                color: #111 !important;
            }
            body.bw-print .brand-bar,
            body.bw-print .box,
            body.bw-print .notes,
            body.bw-print .totals,
            body.bw-print .amount-words,
            body.bw-print th,
            body.bw-print td {
                border-color: #555 !important;
            }
            .page {
                width: 210mm;
                min-height: 287mm;
                margin: 0;
                padding: 12mm 14mm 10mm;
                box-shadow: none;
                page-break-after: avoid;
                break-after: avoid;
            }

            .signatures { margin-top: 22mm; }
            .no-sign-note { margin-top: 18mm; }
            .footer { margin-top: 10px; padding-top: 6px; }

            body.compact-print {
                font-size: 10.5px;
            }

            body.compact-print .page {
                height: 287mm;
                min-height: 0;
                padding: 7mm 9mm 6mm;
                overflow: hidden;
            }

            body.compact-print .brand-bar {
                gap: 10px;
                border-bottom-width: 2px;
                padding-bottom: 6px;
                margin-bottom: 7px;
            }

            body.compact-print .company h1 { font-size: 20px; }
            body.compact-print .company p { margin-top: 2px; line-height: 1.2; }
            body.compact-print .bill-title h2 { font-size: 22px; }
            body.compact-print .status { margin-top: 4px; padding: 3px 8px; font-size: 11px; border-width: 1px; }

            body.compact-print .meta-grid {
                gap: 8px;
                margin-bottom: 7px;
            }

            body.compact-print .box { border-radius: 4px; }
            body.compact-print .box h3 { padding: 4px 6px; font-size: 10px; }
            body.compact-print .box-body { padding: 5px 6px; line-height: 1.25; }
            body.compact-print .kv { grid-template-columns: 78px 1fr; column-gap: 5px; }

            body.compact-print table {
                margin-top: 6px;
                table-layout: fixed;
            }

            body.compact-print th,
            body.compact-print td {
                padding: 3px 5px;
                line-height: 1.12;
            }

            body.compact-print th {
                font-size: 9px;
                letter-spacing: 0;
            }

            body.compact-print tbody td:nth-child(2) {
                overflow-wrap: anywhere;
            }

            body.compact-print .summary {
                grid-template-columns: 1fr 58mm;
                gap: 8px;
                margin-top: 7px;
            }

            body.compact-print .notes {
                min-height: 0;
                padding: 5px 6px;
                line-height: 1.25;
            }

            body.compact-print .totals { border-radius: 4px; }
            body.compact-print .total-row { padding: 4px 6px; gap: 8px; }
            body.compact-print .grand { font-size: 12px; }

            body.compact-print .amount-words {
                margin-top: 6px;
                padding: 5px 6px;
                line-height: 1.25;
            }

            body.compact-print .signatures {
                gap: 24px;
                margin-top: 10mm;
            }

            body.compact-print .signature-line { padding-top: 5px; }
            body.compact-print .no-sign-note { margin-top: 8mm; padding: 7px; line-height: 1.3; }
            body.compact-print .footer { margin-top: 6px; padding-top: 5px; font-size: 9.5px; }

            body.dense-print {
                font-size: 9.5px;
            }

            body.dense-print .page {
                padding: 6mm 8mm;
            }

            body.dense-print .brand-bar {
                padding-bottom: 4px;
                margin-bottom: 5px;
            }

            body.dense-print .company h1 { font-size: 18px; }
            body.dense-print .company p { line-height: 1.12; }
            body.dense-print .bill-title h2 { font-size: 20px; }
            body.dense-print .status { font-size: 10px; }
            body.dense-print .meta-grid { margin-bottom: 5px; }
            body.dense-print .box h3 { padding: 3px 5px; }
            body.dense-print .box-body { padding: 4px 5px; line-height: 1.18; }
            body.dense-print th,
            body.dense-print td { padding: 2px 4px; line-height: 1.05; }
            body.dense-print tbody td:nth-child(2) {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            body.dense-print .summary { margin-top: 5px; }
            body.dense-print .notes,
            body.dense-print .amount-words { padding: 4px 5px; line-height: 1.15; }
            body.dense-print .total-row { padding: 3px 5px; }
            body.dense-print .signatures { margin-top: 6mm; }
            body.dense-print .footer { margin-top: 4px; padding-top: 4px; font-size: 9px; }
        }
    </style>
</head>
<body class="bw-print {{ $selectedOrganization->default_without_signature ? 'no-signature' : '' }} {{ $invoice->items->count() >= 30 ? 'compact-print dense-print' : ($invoice->items->count() >= 25 ? 'compact-print' : '') }}">
    @php
        $serialFormatter = app(\App\Support\SerialNumberParser::class);
        $numberToWords = function (int $number) use (&$numberToWords): string {
            $ones = [
                0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
                5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
                10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
                14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
                18 => 'Eighteen', 19 => 'Nineteen',
            ];
            $tens = [
                2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
                6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
            ];

            if ($number < 20) {
                return $ones[$number];
            }

            if ($number < 100) {
                $words = $tens[intdiv($number, 10)];
                return $number % 10 ? $words.' '.$ones[$number % 10] : $words;
            }

            if ($number < 1000) {
                $words = $ones[intdiv($number, 100)].' Hundred';
                return $number % 100 ? $words.' '.$numberToWords($number % 100) : $words;
            }

            foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $value => $label) {
                if ($number >= $value) {
                    $words = $numberToWords(intdiv($number, $value)).' '.$label;
                    return $number % $value ? $words.' '.$numberToWords($number % $value) : $words;
                }
            }

            return '';
        };

        $amountInWords = function (float $amount) use ($numberToWords): string {
            $taka = (int) floor($amount);
            $paisa = (int) round(($amount - $taka) * 100);
            $words = $numberToWords($taka).' Taka';

            if ($paisa > 0) {
                $words .= ' and '.$numberToWords($paisa).' Paisa';
            }

            return $words.' Only';
        };
    @endphp

    <div class="toolbar">
        <div class="print-mode" aria-label="Print design">
            <label><input type="radio" name="print_mode" value="bw" checked> Black & white</label>
            <label><input type="radio" name="print_mode" value="color"> Color</label>
        </div>
        <label class="print-option">
            <input type="checkbox" id="noSignatureOption" @checked($selectedOrganization->default_without_signature)>
            Print without signature
        </label>
        @include('partials.organization_print_selector')
        <button onclick="recordPrint('invoice', {{ $invoice->id }})" class="btn">Print Bill</button>
        <a href="{{ route('invoices.delivery-challan', ['invoice' => $invoice, 'organization_id' => $selectedOrganization->id]) }}" target="_blank" class="btn secondary">Challan</a>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn light">Back to Invoice</a>
    </div>

    <main class="page">
        <section class="brand-bar">
            <div class="company">
                <div>
                    @if($selectedOrganization->logo_url)<img src="{{ $selectedOrganization->logo_url }}" alt="{{ $selectedOrganization->name }} logo" style="max-width:90px;max-height:52px;margin-bottom:6px">@endif
                    <h1>{{ $selectedOrganization->name }}</h1>
                    <p>
                        {!! nl2br(e($selectedOrganization->address ?: '')) !!}
                        @if($selectedOrganization->mobile)<br>Mobile - {{ $selectedOrganization->mobile }}@endif
                        @if($selectedOrganization->phone)<br>Phone - {{ $selectedOrganization->phone }}@endif
                        @if($selectedOrganization->email)<br>{{ $selectedOrganization->email }}@endif
                        @if($selectedOrganization->website)<br>{{ $selectedOrganization->website }}@endif
                        @if($selectedOrganization->tax_id)<br>Tax/BIN - {{ $selectedOrganization->tax_id }}@endif
                    </p>
                </div>
            </div>
            <div class="bill-title">
                <h2>INVOICE</h2>
                <div class="status {{ $invoice->status }}">{{ $invoice->status }}</div>
            </div>
        </section>

        <section class="meta-grid">
            <div class="box">
                <h3>Invoice To</h3>
                <div class="box-body">
                    <p class="strong">{{ $invoice->customer->name }}</p>
                    <p>{{ $invoice->customer->address }}</p>
                    <p>Phone: {{ $invoice->customer->phone }}</p>
                    <p>Connection ID: {{ $invoice->customer->connection_id }}</p>
                </div>
            </div>

            <div class="box">
                <h3>Invoice Details</h3>
                <div class="box-body">
                    <div class="kv"><span class="muted">Invoice No</span><span>{{ $invoice->invoice_no }}</span></div>
                    <div class="kv"><span class="muted">Bill Month</span><span>{{ $invoice->formatted_billing_month }}</span></div>
                    <div class="kv"><span class="muted">Invoice Type</span><span>{{ ucfirst($invoice->invoice_type ?? 'service') }}</span></div>
                    <div class="kv"><span class="muted">Issue Date</span><span>{{ $invoice->created_at?->format('d M Y') }}</span></div>
                    <div class="kv"><span class="muted">Due Date</span><span>{{ $invoice->due_date?->format('d M Y') ?? 'N/A' }}</span></div>
                </div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width: 42px;">SL</th>
                    <th>Description</th>
                    <th style="width: 72px;" class="center">Qty</th>
                    <th style="width: 110px;" class="right">Rate</th>
                    <th style="width: 120px;" class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoice->items as $index => $item)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>
                            <div>{{ $item->product_name }}</div>
                            @if (filled($item->serial_numbers))
                                <div class="item-serials">Serial: {{ $serialFormatter->formatCompact($item->serial_numbers) }}</div>
                            @endif
                        </td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="right">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="center">1</td>
                        <td>Monthly internet service bill for {{ $invoice->formatted_billing_month }}</td>
                        <td class="center">1</td>
                        <td class="right">{{ number_format($invoice->subtotal, 2) }}</td>
                        <td class="right">{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <section class="summary">
            <div class="notes">
                <p class="strong">Payment Note</p>
                <p style="white-space:pre-line">{{ $paymentNote }}</p>
                @if ($invoice->show_public_note && filled($invoice->public_note))
                    <p class="strong" style="margin-top:8px;">Invoice Note</p>
                    <p style="white-space:pre-line">{{ $invoice->public_note }}</p>
                @endif
                @if($selectedOrganization->show_bank_info_on_invoice && filled($selectedOrganization->bank_account_number))
                    <p class="strong" style="margin-top:8px">Bank Account</p>
                    <p>
                        @if($selectedOrganization->bank_name)Bank: {{ $selectedOrganization->bank_name }}<br>@endif
                        @if($selectedOrganization->bank_account_name)Account Name: {{ $selectedOrganization->bank_account_name }}<br>@endif
                        Account No: {{ $selectedOrganization->bank_account_number }}
                        @if($selectedOrganization->bank_branch)<br>Branch: {{ $selectedOrganization->bank_branch }}@endif
                        @if($selectedOrganization->bank_routing_number)<br>Routing No: {{ $selectedOrganization->bank_routing_number }}@endif
                    </p>
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
                <div class="total-row grand"><span>Total</span><span>{{ number_format($invoice->total, 2) }}</span></div>
                <div class="total-row"><span>Paid</span><span>{{ number_format($invoice->paid_amount, 2) }}</span></div>
                <div class="total-row"><span>Due</span><span>{{ number_format($invoice->due_amount, 2) }}</span></div>
            </div>
        </section>

        <div class="amount-words">
            <span class="strong">Amount in Words:</span>
            {{ $amountInWords((float) $invoice->total) }}
        </div>

        <section class="signatures">
            <div class="signature-line">Party Signature</div>
            <div class="signature-line">Authorized Signature</div>
        </section>

        <div class="no-sign-note">
            Computer-generated bill<br>
            No signature required
        </div>

        <div class="footer">
            @if($selectedOrganization->footer_note)<div style="white-space:pre-line">{{ $selectedOrganization->footer_note }}</div>@endif
            Powered by Ultimate Solution
        </div>
    </main>

    <script>
        const noSignatureOption = document.getElementById('noSignatureOption');

        noSignatureOption.addEventListener('change', function () {
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
