<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function moveStock(Product $product, string $type, int $quantity, ?string $reason = null, ?string $referenceNo = null): StockMovement
    {
        if (! $product->track_inventory) {
            throw new InvalidArgumentException('This product does not track inventory.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Stock quantity must be greater than zero.');
        }

        if (! in_array($type, ['in', 'out', 'use'], true)) {
            throw new InvalidArgumentException('Stock movement type must be in, out, or use.');
        }

        if (in_array($type, ['out', 'use'], true) && $quantity > $product->stock_quantity) {
            throw new InvalidArgumentException('Stock out quantity cannot be greater than available stock.');
        }

        return DB::transaction(function () use ($product, $type, $quantity, $reason, $referenceNo) {
            $product->stock_quantity += $type === 'in' ? $quantity : -$quantity;
            $product->save();

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'reference_no' => $referenceNo ?: now()->format('YmdHis'),
            ]);
        });
    }
}
