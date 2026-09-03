@php
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

    $netTotal = (float) $invoice->total;
    $netDiscount = (float) $invoice->discount;
    $commissionAmount = (float) ($invoice->reseller_commission_amount ?? 0);
    $serialFormatter = app(\App\Support\SerialNumberParser::class);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->pdf_filename_base }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm 11mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.35; }
        p { margin: 0 0 2px; }
        .header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #111; margin-bottom: 12px; }
        .header td { padding: 0 0 10px; vertical-align: top; }
        .company-name { margin: 0 0 4px; font-size: 23px; font-weight: 700; }
        .invoice-heading { text-align: right; }
        .invoice-heading h1 { margin: 0 0 7px; font-size: 25px; }
        .status { display: inline-block; border: 1.5px solid #111; padding: 4px 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .two-column { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 12px; table-layout: fixed; }
        .two-column > tbody > tr > td { width: 50%; vertical-align: top; }
        .two-column > tbody > tr > td:first-child { padding-right: 6px; }
        .two-column > tbody > tr > td:last-child { padding-left: 6px; }
        .box { width: 100%; border: 1px solid #555; border-collapse: collapse; }
        .box th { padding: 6px 8px; background: #f2f2f2; border-bottom: 1px solid #555; text-align: left; font-size: 10px; text-transform: uppercase; }
        .box td { padding: 7px 8px; vertical-align: top; }
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table td { padding: 1px 0; border: 0; }
        .detail-table td:first-child { width: 42%; color: #444; }
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items thead { display: table-header-group; }
        .items th, .items td { border: 1px solid #555; padding: 6px 7px; vertical-align: top; }
        .items th { background: #222; color: #fff; font-size: 9px; text-transform: uppercase; }
        .items tr { page-break-inside: avoid; }
        .center { text-align: center; }
        .right { text-align: right; }
        .serials { margin-top: 2px; color: #444; font-size: 8.5px; overflow-wrap: anywhere; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 12px; table-layout: fixed; page-break-inside: avoid; }
        .summary > tbody > tr > td { vertical-align: top; }
        .summary > tbody > tr > td:first-child { width: 58%; padding-right: 6px; }
        .summary > tbody > tr > td:last-child { width: 42%; padding-left: 6px; }
        .notes, .amount-words { border: 1px solid #555; padding: 7px 8px; }
        .notes { min-height: 82px; }
        .note-title { margin-bottom: 3px; font-weight: 700; }
        .bank { margin-top: 6px; }
        .totals { width: 100%; border: 1px solid #555; border-collapse: collapse; }
        .totals td { padding: 5px 7px; border-bottom: 1px solid #777; }
        .totals tr:last-child td { border-bottom: 0; }
        .totals .grand td { background: #222; color: #fff; font-size: 12px; font-weight: 700; }
        .amount-words { margin-top: 10px; page-break-inside: avoid; }
        .signatures { width: 100%; margin-top: 24mm; border-collapse: separate; border-spacing: 16mm 0; page-break-inside: avoid; }
        .signatures td { width: 50%; border-top: 1px solid #111; padding-top: 5px; text-align: center; font-weight: 700; }
        .no-signature { margin-top: 18mm; border: 1px solid #555; padding: 10px; background: #f2f2f2; text-align: center; font-weight: 700; page-break-inside: avoid; }
        .footer { margin-top: 12px; padding-top: 6px; border-top: 1px solid #777; color: #444; text-align: center; font-size: 9px; page-break-inside: avoid; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="company-name">{{ $selectedOrganization->name }}</div>
                <p>{!! nl2br(e($selectedOrganization->address ?: '')) !!}</p>
                @if($selectedOrganization->mobile)<p>Mobile - {{ $selectedOrganization->mobile }}</p>@endif
                @if($selectedOrganization->phone)<p>Phone - {{ $selectedOrganization->phone }}</p>@endif
                @if($selectedOrganization->email)<p>{{ $selectedOrganization->email }}</p>@endif
                @if($selectedOrganization->website)<p>{{ $selectedOrganization->website }}</p>@endif
                @if($selectedOrganization->tax_id)<p>Tax/BIN - {{ $selectedOrganization->tax_id }}</p>@endif
            </td>
            <td class="invoice-heading">
                <h1>INVOICE</h1>
                <span class="status">{{ $invoice->status }}</span>
            </td>
        </tr>
    </table>

    <table class="two-column">
        <tr>
            <td>
                <table class="box">
                    <tr><th>Invoice To</th></tr>
                    <tr><td>
                        <p><strong>{{ $invoice->customer->name }}</strong></p>
                        @if($invoice->customer->address)<p>{{ $invoice->customer->address }}</p>@endif
                        @if($invoice->customer->phone)<p>Phone: {{ $invoice->customer->phone }}</p>@endif
                        @if($invoice->customer->connection_id)<p>Connection ID: {{ $invoice->customer->connection_id }}</p>@endif
                    </td></tr>
                </table>
            </td>
            <td>
                <table class="box">
                    <tr><th>Invoice Details</th></tr>
                    <tr><td>
                        <table class="detail-table">
                            <tr><td>Invoice No</td><td>{{ $invoice->invoice_no }}</td></tr>
                            <tr><td>Bill Month</td><td>{{ $invoice->formatted_billing_month }}</td></tr>
                            <tr><td>Invoice Type</td><td>{{ ucfirst($invoice->invoice_type ?? 'service') }}</td></tr>
                            <tr><td>Issue Date</td><td>{{ $invoice->created_at?->format('d/m/Y') }}</td></tr>
                            <tr><td>Due Date</td><td>{{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}</td></tr>
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:7%">SL</th>
                <th>Description</th>
                <th style="width:10%">Qty</th>
                <th style="width:16%">Rate</th>
                <th style="width:17%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product_name }}@if($invoice->invoice_type === 'service') ({{ $invoice->formatted_billing_month }})@endif
                        @if(filled($item->serial_numbers))<div class="serials">Serial: {{ $serialFormatter->formatCompact($item->serial_numbers) }}</div>@endif
                    </td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">{{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center">1</td>
                    <td>Monthly Internet Service Bill for {{ $invoice->formatted_billing_month }}</td>
                    <td class="center">1</td>
                    <td class="right">{{ number_format($invoice->subtotal, 2) }}</td>
                    <td class="right">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="notes">
                    <div class="note-title">Payment Note</div>
                    <div style="white-space:pre-line">{{ $paymentNote }}</div>
                    @if($invoice->show_public_note && filled($invoice->public_note))
                        <div class="note-title" style="margin-top:6px">Invoice Note</div>
                        <div style="white-space:pre-line">{{ $invoice->public_note }}</div>
                    @endif
                    @if($selectedOrganization->show_bank_info_on_invoice && filled($selectedOrganization->bank_account_number))
                        <div class="bank">
                            <div class="note-title">Bank Account</div>
                            @if($selectedOrganization->bank_name)<div>Bank: {{ $selectedOrganization->bank_name }}</div>@endif
                            @if($selectedOrganization->bank_account_name)<div>Account Name: {{ $selectedOrganization->bank_account_name }}</div>@endif
                            <div>Account No: {{ $selectedOrganization->bank_account_number }}</div>
                            @if($selectedOrganization->bank_branch)<div>Branch: {{ $selectedOrganization->bank_branch }}</div>@endif
                            @if($selectedOrganization->bank_routing_number)<div>Routing No: {{ $selectedOrganization->bank_routing_number }}</div>@endif
                        </div>
                    @endif
                </div>
            </td>
            <td>
                <table class="totals">
                    <tr><td>Subtotal</td><td class="right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
                    @if($netDiscount > 0)<tr><td>Discount</td><td class="right">{{ number_format($netDiscount, 2) }}</td></tr>@endif
                    @if($commissionAmount > 0)<tr><td>Reseller commission</td><td class="right">{{ number_format($commissionAmount, 2) }}</td></tr>@endif
                    @if((float) ($invoice->vat ?? 0) > 0)<tr><td>VAT</td><td class="right">{{ number_format($invoice->vat, 2) }}</td></tr>@endif
                    <tr class="grand"><td>Total</td><td class="right">{{ number_format($netTotal, 2) }}</td></tr>
                    <tr><td>Paid</td><td class="right">{{ number_format($invoice->paid_amount, 2) }}</td></tr>
                    <tr><td>Due</td><td class="right">{{ number_format($invoice->due_amount, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="amount-words"><strong>Amount in Words:</strong> {{ $numberToWords((int) round($netTotal)) }} Taka Only</div>

    @if($selectedOrganization->default_without_signature)
        <div class="no-signature">Computer-generated bill<br>No signature required</div>
    @else
        <table class="signatures"><tr><td>Party Signature</td><td>Authorized Signature</td></tr></table>
    @endif

    <div class="footer">
        @if($selectedOrganization->footer_note)<div style="white-space:pre-line">{{ $selectedOrganization->footer_note }}</div>@endif
        <div>Powered by Ultimate Solution</div>
    </div>
</body>
</html>
