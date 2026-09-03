<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->pdf_filename_base }} Challan</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm 11mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.35; }
        p { margin: 0 0 2px; }
        .header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #111; margin-bottom: 12px; }
        .header td { padding: 0 0 10px; vertical-align: top; }
        .company-name { margin: 0 0 4px; font-size: 23px; font-weight: 700; }
        .doc-heading { text-align: right; }
        .doc-heading h1 { margin: 0 0 7px; font-size: 24px; letter-spacing: 1px; }
        .doc-no { font-size: 10px; color: #444; font-weight: 700; }
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
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 4px; }
        .items thead { display: table-header-group; }
        .items th, .items td { border: 1px solid #555; padding: 6px 7px; vertical-align: top; }
        .items th { background: #222; color: #fff; font-size: 9px; text-transform: uppercase; }
        .items tr { page-break-inside: avoid; }
        .center { text-align: center; }
        .notes { margin-top: 12px; border: 1px solid #555; padding: 7px 8px; min-height: 60px; page-break-inside: avoid; }
        .note-title { margin-bottom: 3px; font-weight: 700; }
        .signatures { width: 100%; margin-top: 22mm; border-collapse: separate; border-spacing: 12mm 0; page-break-inside: avoid; }
        .signatures td { border-top: 1px solid #111; padding-top: 5px; text-align: center; font-weight: 700; }
        .no-signature { margin-top: 16mm; border: 1px solid #555; padding: 10px; background: #f2f2f2; text-align: center; font-weight: 700; page-break-inside: avoid; }
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
            <td class="doc-heading">
                <h1>DELIVERY CHALLAN</h1>
                <div class="doc-no">Ref: {{ $invoice->invoice_no }}</div>
            </td>
        </tr>
    </table>

    <table class="two-column">
        <tr>
            <td>
                <table class="box">
                    <tr><th>Delivered To</th></tr>
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
                    <tr><th>Challan Details</th></tr>
                    <tr><td>
                        <table class="detail-table">
                            <tr><td>Challan No</td><td>DC-{{ $invoice->invoice_no }}</td></tr>
                            <tr><td>Invoice Ref</td><td>{{ $invoice->invoice_no }}</td></tr>
                            <tr><td>Date</td><td>{{ now()->format('d/m/Y') }}</td></tr>
                            <tr><td>Bill Month</td><td>{{ $invoice->formatted_billing_month }}</td></tr>
                        </table>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:8%">SL</th>
                <th>Description</th>
                <th style="width:12%">Qty</th>
                <th style="width:26%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $index => $item)
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
        <div class="note-title">Delivery Note</div>
        <div>The above goods/services have been delivered to the party in good condition.</div>
        @if($invoice->show_public_note && filled($invoice->public_note))
            <div class="note-title" style="margin-top:6px">Invoice Note</div>
            <div style="white-space:pre-line">{{ $invoice->public_note }}</div>
        @endif
    </div>

    @if($withoutSignature ?? $selectedOrganization->default_without_signature)
        <div class="no-signature">Computer-generated delivery challan<br>No signature required</div>
    @else
        <table class="signatures">
            <tr>
                <td>Prepared By</td>
                <td>Delivered By</td>
                <td>Received By</td>
            </tr>
        </table>
    @endif

    <div class="footer">
        @if($selectedOrganization->footer_note)<div style="white-space:pre-line">{{ $selectedOrganization->footer_note }}</div>@endif
        <div>Powered by Ultimate Solution</div>
    </div>
</body>
</html>
