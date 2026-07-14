<table>
    <thead><tr><th>Date</th><th>Product</th><th>Warehouse</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>Related Warehouse</th><th>Serials</th><th>Reference</th><th>Reason</th><th>Entry By</th></tr></thead>
    <tbody>
    @forelse($movements as $movement)
        <tr>
            <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
            <td>
                @if ($movement->product)
                    <a href="{{ route('products.show', $movement->product) }}">{{ $movement->product->name }}</a>
                @else
                    Deleted product
                @endif
            </td>
            <td>
                @if ($movement->warehouse)
                    <a href="{{ route('warehouses.show', $movement->warehouse) }}">{{ $movement->warehouse->name }}</a>
                @else
                    Legacy / N/A
                @endif
            </td>
            <td><span class="badge">{{ str_replace('_', ' ', strtoupper($movement->type)) }}</span></td>
            <td>{{ $movement->quantity }}</td>
            <td>{{ $movement->balance_before ?? 'N/A' }}</td>
            <td>{{ $movement->balance_after ?? 'N/A' }}</td>
            <td>
                @if ($movement->relatedWarehouse)
                    <a href="{{ route('warehouses.show', $movement->relatedWarehouse) }}">{{ $movement->relatedWarehouse->name }}</a>
                @else
                    N/A
                @endif
            </td>
            <td>{{ $movement->serial_numbers ?? 'N/A' }}</td>
            <td>
                @if ($movement->reference_no && isset($referenceLinks[$movement->reference_no]))
                    <a href="{{ $referenceLinks[$movement->reference_no] }}">{{ $movement->reference_no }}</a>
                @else
                    {{ $movement->reference_no ?? 'N/A' }}
                @endif
            </td>
            <td>{{ $movement->reason ?? 'N/A' }}</td>
            <td>{{ $movement->entry_by_type === 'user' ? 'User #'.$movement->entry_by : ($movement->entry_by ?? 'system') }}</td>
        </tr>
    @empty
        <tr><td colspan="12">No stock movement found.</td></tr>
    @endforelse
    </tbody>
</table>
