<table>
    <thead><tr><th>Date</th><th>Product</th><th>Warehouse</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>Related Warehouse</th><th>Serials</th><th>Reference</th><th>Reason</th><th>Entry By</th></tr></thead>
    <tbody>
    @forelse($movements as $movement)
        <tr>
            <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
            <td>{{ $movement->product?->name ?? 'Deleted product' }}</td>
            <td>{{ $movement->warehouse?->name ?? 'Legacy / N/A' }}</td>
            <td><span class="badge">{{ str_replace('_', ' ', strtoupper($movement->type)) }}</span></td>
            <td>{{ $movement->quantity }}</td>
            <td>{{ $movement->balance_before ?? 'N/A' }}</td>
            <td>{{ $movement->balance_after ?? 'N/A' }}</td>
            <td>{{ $movement->relatedWarehouse?->name ?? 'N/A' }}</td>
            <td>{{ $movement->serial_numbers ?? 'N/A' }}</td>
            <td>{{ $movement->reference_no ?? 'N/A' }}</td>
            <td>{{ $movement->reason ?? 'N/A' }}</td>
            <td>{{ $movement->entry_by_type === 'user' ? 'User #'.$movement->entry_by : ($movement->entry_by ?? 'system') }}</td>
        </tr>
    @empty
        <tr><td colspan="12">No stock movement found.</td></tr>
    @endforelse
    </tbody>
</table>
