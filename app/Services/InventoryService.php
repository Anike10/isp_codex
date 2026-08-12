<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InventoryService
{
    public function defaultWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()->where('is_default', true)->first();

        return $warehouse ?: $this->repairDefaultWarehouse();
    }

    public function moveStock(
        Product $product,
        string $type,
        int $quantity,
        ?string $reason = null,
        ?string $referenceNo = null,
        int $seriallessQuantity = 0,
        ?Warehouse $warehouse = null,
        array $serialNumbers = [],
    ): StockMovement {
        if (! $product->track_inventory) {
            throw new InvalidArgumentException('This product does not track inventory.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Stock quantity must be greater than zero.');
        }

        if (! in_array($type, ['in', 'out', 'use'], true)) {
            throw new InvalidArgumentException('Stock movement type must be in, out, or use.');
        }

        if ($seriallessQuantity < 0 || $seriallessQuantity > $quantity) {
            throw new InvalidArgumentException('Serial-less quantity must be between zero and stock quantity.');
        }

        $warehouse ??= $this->defaultWarehouse();

        return DB::transaction(function () use ($product, $type, $quantity, $reason, $referenceNo, $seriallessQuantity, $warehouse, $serialNumbers): StockMovement {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $warehouseStock = $this->lockedWarehouseStock($product->id, $warehouse->id);
            $balanceBefore = (int) $warehouseStock->quantity;

            if (in_array($type, ['out', 'use'], true) && $quantity > $balanceBefore) {
                throw new InvalidArgumentException('Stock out quantity cannot be greater than available warehouse stock.');
            }

            if ($lockedProduct->track_serial_numbers && in_array($type, ['out', 'use'], true)) {
                $trackedSerialCount = $lockedProduct->serials()
                    ->where('warehouse_id', $warehouse->id)
                    ->where('status', 'in_stock')
                    ->count();
                $availableSerialless = max(0, $balanceBefore - $trackedSerialCount);

                if ($seriallessQuantity > $availableSerialless) {
                    throw new InvalidArgumentException('Serial-less quantity exceeds available serial-less stock in this warehouse.');
                }
            }

            $delta = $type === 'in' ? $quantity : -$quantity;
            $warehouseStock->quantity = $balanceBefore + $delta;
            $warehouseStock->save();

            $lockedProduct->stock_quantity = max(0, (int) $lockedProduct->stock_quantity + $delta);
            $lockedProduct->save();
            $product->setAttribute('stock_quantity', $lockedProduct->stock_quantity);

            return StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => $type,
                'quantity' => $quantity,
                'serialless_quantity' => $seriallessQuantity,
                'serial_numbers' => $serialNumbers === [] ? null : implode(', ', array_values(array_unique($serialNumbers))),
                'balance_before' => $balanceBefore,
                'balance_after' => $warehouseStock->quantity,
                'reason' => $reason,
                'reference_no' => $referenceNo ?: now()->format('YmdHis'),
            ]);
        });
    }

    public function transfer(
        Product $product,
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        int $quantity,
        array $serialNumbers = [],
        int $seriallessQuantity = 0,
        ?string $reason = null,
        ?string $referenceNo = null,
    ): array {
        if (! $product->track_inventory) {
            throw new InvalidArgumentException('This product does not track inventory.');
        }

        if ($fromWarehouse->is($toWarehouse)) {
            throw new InvalidArgumentException('Source and destination warehouses must be different.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be greater than zero.');
        }

        $serialNumbers = array_values(array_unique($serialNumbers));

        if ($product->track_serial_numbers && count($serialNumbers) + $seriallessQuantity !== $quantity) {
            throw new InvalidArgumentException('Serial count plus serial-less quantity must match transfer quantity.');
        }

        if (! $product->track_serial_numbers) {
            $serialNumbers = [];
            $seriallessQuantity = 0;
        }

        return DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $quantity, $serialNumbers, $seriallessQuantity, $reason, $referenceNo): array {
            $fromStock = $this->lockedWarehouseStock($product->id, $fromWarehouse->id);
            $toStock = $this->lockedWarehouseStock($product->id, $toWarehouse->id);
            $fromBefore = (int) $fromStock->quantity;
            $toBefore = (int) $toStock->quantity;

            if ($quantity > $fromBefore) {
                throw new InvalidArgumentException('Transfer quantity cannot be greater than source warehouse stock.');
            }

            if ($product->track_serial_numbers) {
                $availableSerials = $product->serials()
                    ->where('warehouse_id', $fromWarehouse->id)
                    ->where('status', 'in_stock')
                    ->whereIn('serial_number', $serialNumbers)
                    ->lockForUpdate()
                    ->pluck('serial_number')
                    ->all();
                $missingSerials = array_values(array_diff($serialNumbers, $availableSerials));

                if ($missingSerials !== []) {
                    throw new InvalidArgumentException('These serials are not available in the source warehouse: '.implode(', ', $missingSerials));
                }

                $trackedSerialCount = $product->serials()
                    ->where('warehouse_id', $fromWarehouse->id)
                    ->where('status', 'in_stock')
                    ->count();
                $availableSerialless = max(0, $fromBefore - $trackedSerialCount);

                if ($seriallessQuantity > $availableSerialless) {
                    throw new InvalidArgumentException('Serial-less transfer quantity exceeds source warehouse serial-less stock.');
                }

                $product->serials()
                    ->whereIn('serial_number', $serialNumbers)
                    ->update(['warehouse_id' => $toWarehouse->id]);
            }

            $fromStock->update(['quantity' => $fromBefore - $quantity]);
            $toStock->update(['quantity' => $toBefore + $quantity]);

            $referenceNo ??= 'TRF-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
            $reason ??= 'Warehouse transfer';
            $outMovement = StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $fromWarehouse->id,
                'related_warehouse_id' => $toWarehouse->id,
                'type' => 'transfer_out',
                'quantity' => $quantity,
                'serialless_quantity' => $seriallessQuantity,
                'serial_numbers' => $serialNumbers === [] ? null : implode(', ', $serialNumbers),
                'balance_before' => $fromBefore,
                'balance_after' => $fromBefore - $quantity,
                'reason' => $reason,
                'reference_no' => $referenceNo,
            ]);
            $inMovement = StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $toWarehouse->id,
                'related_warehouse_id' => $fromWarehouse->id,
                'type' => 'transfer_in',
                'quantity' => $quantity,
                'serialless_quantity' => $seriallessQuantity,
                'serial_numbers' => $serialNumbers === [] ? null : implode(', ', $serialNumbers),
                'balance_before' => $toBefore,
                'balance_after' => $toBefore + $quantity,
                'reason' => $reason,
                'reference_no' => $referenceNo,
            ]);

            return [$outMovement, $inMovement];
        });
    }

    public function transferMany(Warehouse $fromWarehouse, Warehouse $toWarehouse, array $items, ?string $reason = null): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('At least one product is required for transfer.');
        }

        $referenceNo = 'TRF-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        return DB::transaction(function () use ($fromWarehouse, $toWarehouse, $items, $reason, $referenceNo): array {
            $movements = [];

            foreach ($items as $item) {
                $movements[] = $this->transfer(
                    $item['product'],
                    $fromWarehouse,
                    $toWarehouse,
                    (int) $item['quantity'],
                    $item['serial_numbers'] ?? [],
                    (int) ($item['serialless_quantity'] ?? 0),
                    $reason,
                    $referenceNo,
                );
            }

            return $movements;
        });
    }

    private function repairDefaultWarehouse(): Warehouse
    {
        return DB::transaction(function (): Warehouse {
            $existingDefault = Warehouse::query()->where('is_default', true)->first();

            if ($existingDefault) {
                return $existingDefault;
            }

            $candidate = Warehouse::query()
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($candidate) {
                if (! $candidate->is_default || ! $candidate->is_active) {
                    $candidate->update([
                        'is_default' => true,
                        'is_active' => true,
                    ]);
                    $candidate->refresh();
                }

                return $candidate;
            }

            $baseCode = 'MAIN';
            $suffix = 1;
            $code = $baseCode;

            while (Warehouse::query()->where('code', $code)->exists()) {
                $code = $baseCode.'-'.$suffix++;
            }

            return Warehouse::query()->create([
                'entry_by' => 'system',
                'entry_by_type' => 'system',
                'name' => 'Main Warehouse',
                'code' => $code,
                'is_default' => true,
                'is_active' => true,
            ]);
        });
    }

    private function lockedWarehouseStock(int $productId, int $warehouseId): ProductWarehouseStock
    {
        $initialQuantity = 0;

        if (! ProductWarehouseStock::query()->where('product_id', $productId)->exists()
            && Warehouse::query()->whereKey($warehouseId)->where('is_default', true)->exists()) {
            $initialQuantity = max(0, (int) Product::query()->whereKey($productId)->value('stock_quantity'));
        }

        ProductWarehouseStock::query()->firstOrCreate([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ], ['quantity' => $initialQuantity]);

        return ProductWarehouseStock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
