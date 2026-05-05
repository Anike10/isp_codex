<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function moveStock(Product $product, string $type, int $quantity, ?string $reason = null): StockMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Stock quantity must be greater than zero.');
        }

        if (! in_array($type, ['in', 'out'], true)) {
            throw new InvalidArgumentException('Stock movement type must be in or out.');
        }

        if ($type === 'out' && $quantity > $product->stock_quantity) {
            throw new InvalidArgumentException('Stock out quantity cannot be greater than available stock.');
        }

        return DB::transaction(function () use ($product, $type, $quantity, $reason) {
            $product->stock_quantity += $type === 'in' ? $quantity : -$quantity;
            $product->save();

            return StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'reference_no' => now()->format('YmdHis'),
            ]);
        });
    }
}
