@props(['versions'])

@php
    $versions = $versions ?? collect();
    $money = fn ($value): string => number_format((float) ($value ?? 0), 2);
    $dateOnly = function ($value): string {
        if (! $value) {
            return 'N/A';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $fieldLabel = fn (string $field): string => collect(explode('.', $field))
        ->map(fn (string $part): string => str_replace('_', ' ', ucfirst($part)))
        ->implode(' / ');
    $displayValue = fn ($value): string => is_bool($value)
        ? ($value ? 'Yes' : 'No')
        : (($value === null || $value === '') ? 'N/A' : (string) $value);
    $flattenFields = function (array $values, string $prefix = '') use (&$flattenFields): array {
        $flattened = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $flattened += $flattenFields($value, $path);
            } else {
                $flattened[$path] = $value;
            }
        }

        return $flattened;
    };
@endphp

<section class="card" style="margin-top:16px">
    <style>
        .version-preview {
            margin-top: 12px;
            padding: 16px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            background: #fff7ed;
        }
        .version-history-list {
            display: grid;
            gap: 12px;
        }
        .version-history-card {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 12px;
            background: #fff;
        }
        .version-history-summary {
            display: grid;
            grid-template-columns: minmax(130px, .8fr) minmax(150px, 1fr) minmax(220px, 2fr);
            gap: 12px;
            align-items: start;
        }
        .version-history-cell {
            display: grid;
            gap: 5px;
        }
        .version-history-cell span:first-child {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .version-history-details {
            margin-top: 10px;
        }
        .version-preview-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #fed7aa;
        }
        .version-preview-title {
            display: grid;
            gap: 5px;
        }
        .version-preview-title strong {
            font-size: 20px;
            line-height: 1.2;
        }
        .version-preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }
        .version-preview-box {
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 12px;
            background: #fff;
        }
        .version-preview-box h3 {
            margin: 0 0 8px;
            font-size: 15px;
        }
        .version-preview-row {
            display: grid;
            grid-template-columns: 130px minmax(0, 1fr);
            gap: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #ffedd5;
        }
        .version-preview-row:last-child {
            border-bottom: 0;
        }
        .version-preview-total {
            display: grid;
            gap: 6px;
            justify-content: end;
            margin-top: 12px;
        }
        .version-preview-total div {
            display: grid;
            grid-template-columns: 120px 120px;
            gap: 12px;
            text-align: right;
        }
        .version-preview-fallback {
            display: grid;
            gap: 6px;
            margin-top: 12px;
        }
        .version-preview-fallback div {
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr);
            gap: 10px;
            padding: 8px;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            background: #fff;
        }
        @media (max-width: 780px) {
            .version-preview-header,
            .version-preview-grid,
            .version-preview-row,
            .version-preview-fallback div,
            .version-history-summary {
                grid-template-columns: 1fr;
            }
            .version-preview-header {
                display: grid;
            }
            .version-preview-total {
                justify-content: stretch;
            }
            .version-preview-total div {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
    <h2>Edit History</h2>
    @if ($versions->isEmpty())
        <p class="muted">No edits recorded yet.</p>
    @else
        <div class="version-history-list">
            @foreach ($versions as $version)
                <article class="version-history-card">
                    <div class="version-history-summary">
                        <div class="version-history-cell">
                            <span>Date</span>
                            <strong>{{ $version->created_at?->format('Y-m-d H:i') }}</strong>
                        </div>
                        <div class="version-history-cell">
                            <span>Edited By</span>
                            <strong>{{ $version->edited_by_name ?? $version->edited_by ?? 'System' }}</strong>
                            <small class="muted">{{ $version->edited_by_type ?? 'system' }}</small>
                        </div>
                        <div class="version-history-cell">
                            <span>Changed Fields</span>
                            <div>
                                @foreach (array_slice($version->changed_fields ?? [], 0, 12) as $field)
                                    <span class="badge pending">{{ $fieldLabel($field) }}</span>
                                @endforeach
                                @if (count($version->changed_fields ?? []) > 12)
                                    <span class="muted">+{{ count($version->changed_fields) - 12 }} more</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <details class="version-history-details">
                        <summary class="btn light">View Old Version</summary>
                            @php
                                $old = $version->old_values ?? [];
                                $customer = $old['customer'] ?? [];
                                $items = $old['items'] ?? [];
                                $looksLikeDocument = array_key_exists('invoice_no', $old) || array_key_exists('quotation_no', $old) || array_key_exists('billing_month', $old) || array_key_exists('items', $old);
                                $isQuotation = array_key_exists('quotation_no', $old) || array_key_exists('quotation_date', $old);
                                $documentNo = $old['invoice_no'] ?? $old['quotation_no'] ?? ($isQuotation ? 'Old Quotation Version' : 'Old Invoice Version');
                                $noteFields = collect([
                                    'Payment Note' => $old['payment_note'] ?? null,
                                    'Public Note' => $old['public_note'] ?? null,
                                    'Private Note' => $old['private_note'] ?? null,
                                    'Service Note' => $old['service_note'] ?? null,
                                ])->filter(fn ($value) => $value !== null && $value !== '');
                                $documentDetails = $flattenFields(collect($old)->except([
                                    'invoice_no', 'quotation_no', 'quotation_date', 'valid_until', 'billing_month',
                                    'invoice_type', 'status', 'due_date', 'finalized_at', 'customer', 'items',
                                    'subtotal', 'discount', 'vat', 'total', 'paid_amount', 'due_amount',
                                    'payment_note', 'public_note', 'private_note', 'service_note',
                                ])->all());
                                $fallbackFields = collect($flattenFields($old))->take(50);
                            @endphp

                            @if ($looksLikeDocument)
                                <div class="version-preview">
                                    <div class="version-preview-header">
                                        <div class="version-preview-title">
                                            <strong>{{ $documentNo }}</strong>
                                            <span class="muted">Old version saved {{ $version->created_at?->format('Y-m-d H:i') }}</span>
                                            <span class="muted">Edited by {{ $version->edited_by_name ?? $version->edited_by ?? 'System' }}</span>
                                        </div>
                                    </div>

                                    <div class="version-preview-grid">
                                        <div class="version-preview-box">
                                            <h3>{{ $isQuotation ? 'Quotation' : 'Invoice' }}</h3>
                                            @if ($isQuotation)
                                                <div class="version-preview-row"><span class="muted">Date</span><span>{{ $dateOnly($old['quotation_date'] ?? null) }}</span></div>
                                                <div class="version-preview-row"><span class="muted">Valid Until</span><span>{{ $dateOnly($old['valid_until'] ?? null) }}</span></div>
                                            @endif
                                            <div class="version-preview-row"><span class="muted">{{ $isQuotation ? 'Reference Month' : 'Month' }}</span><span>{{ $old['billing_month'] ?? 'N/A' }}</span></div>
                                            <div class="version-preview-row"><span class="muted">Type</span><span>{{ ucfirst((string) ($old['invoice_type'] ?? 'N/A')) }}</span></div>
                                            <div class="version-preview-row"><span class="muted">Status</span><span>{{ ucfirst((string) ($old['status'] ?? 'N/A')) }}</span></div>
                                            @if (! $isQuotation)
                                                <div class="version-preview-row"><span class="muted">Due Date</span><span>{{ $dateOnly($old['due_date'] ?? null) }}</span></div>
                                                <div class="version-preview-row"><span class="muted">Finalized</span><span>{{ $dateOnly($old['finalized_at'] ?? null) }}</span></div>
                                            @endif
                                        </div>

                                        <div class="version-preview-box">
                                            <h3>Party</h3>
                                            <div class="version-preview-row"><span class="muted">Name</span><span>{{ $customer['name'] ?? 'N/A' }}</span></div>
                                            <div class="version-preview-row"><span class="muted">Phone</span><span>{{ $customer['phone'] ?? 'N/A' }}</span></div>
                                            <div class="version-preview-row"><span class="muted">Connection</span><span>{{ $customer['connection_id'] ?? 'N/A' }}</span></div>
                                            <div class="version-preview-row"><span class="muted">Address</span><span>{{ $customer['address'] ?? 'N/A' }}</span></div>
                                        </div>
                                    </div>

                                    <div class="version-preview-box">
                                        <h3>Items</h3>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th style="width:56px">SL</th>
                                                    <th>Product</th>
                                                    <th>Qty</th>
                                                    <th>Unit Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @forelse ($items as $itemIndex => $item)
                                                <tr>
                                                    <td>{{ $itemIndex + 1 }}</td>
                                                    <td>
                                                        {{ $item['product_name'] ?? 'N/A' }}
                                                        @if (! empty($item['serial_numbers']))
                                                            <div class="muted">Serials: {{ $item['serial_numbers'] }}</div>
                                                        @endif
                                                        @if (! empty($item['serialless_quantity']))
                                                            <div class="muted">Serial-less Qty: {{ $item['serialless_quantity'] }}</div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $item['quantity'] ?? 0 }}</td>
                                                    <td>{{ $money($item['unit_price'] ?? 0) }}</td>
                                                    <td>{{ $money($item['total'] ?? 0) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5">No old items found.</td></tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                        <div class="version-preview-total">
                                            <div><span class="muted">Subtotal</span><strong>{{ $money($old['subtotal'] ?? 0) }}</strong></div>
                                            <div><span class="muted">Discount</span><strong>{{ $money($old['discount'] ?? 0) }}</strong></div>
                                            <div><span class="muted">VAT</span><strong>{{ $money($old['vat'] ?? 0) }}</strong></div>
                                            <div><span class="muted">{{ $isQuotation ? 'Quoted Total' : 'Total' }}</span><strong>{{ $money($old['total'] ?? 0) }}</strong></div>
                                            @if (! $isQuotation)
                                                <div><span class="muted">Paid</span><strong>{{ $money($old['paid_amount'] ?? 0) }}</strong></div>
                                                <div><span class="muted">Due</span><strong>{{ $money($old['due_amount'] ?? 0) }}</strong></div>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($noteFields->isNotEmpty())
                                        <div class="version-preview-box" style="margin-top:14px">
                                            <h3>Notes</h3>
                                            @foreach ($noteFields as $label => $value)
                                                <div class="version-preview-row"><span class="muted">{{ $label }}</span><span>{{ $value }}</span></div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($documentDetails !== [])
                                        <div class="version-preview-box" style="margin-top:14px">
                                            <h3>Other Details</h3>
                                            @foreach (array_slice($documentDetails, 0, 30, true) as $field => $value)
                                                <div class="version-preview-row"><span class="muted">{{ $fieldLabel($field) }}</span><span>{{ $displayValue($value) }}</span></div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="version-preview">
                                    <div class="version-preview-header">
                                        <div class="version-preview-title">
                                            <strong>Old Version</strong>
                                            <span class="muted">Saved {{ $version->created_at?->format('Y-m-d H:i') }}</span>
                                        </div>
                                    </div>
                                    <div class="version-preview-fallback">
                                        @foreach ($fallbackFields as $field => $value)
                                            <div><span class="muted">{{ $fieldLabel($field) }}</span><span>{{ $displayValue($value) }}</span></div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                    </details>
                </article>
            @endforeach
        </div>
        @if ($versions instanceof \Illuminate\Contracts\Pagination\Paginator)
            <div style="margin-top:16px">{{ $versions->links() }}</div>
        @endif
    @endif
</section>
