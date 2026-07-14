<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAssetAssignment;
use App\Models\EmployeeAssetReturn;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductWarehouseStock;
use App\Models\UsedProductWarehouseStock;
use App\Models\Warehouse;
use App\Support\SerialNumberParser;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeAssetService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly SerialNumberParser $serialNumberParser,
    ) {}

    public function assign(Employee $employee, Product $product, Warehouse $warehouse, array $data, ?int $userId): EmployeeAssetAssignment
    {
        if (! $product->track_inventory) {
            throw new InvalidArgumentException('Only inventory-tracked products can be assigned.');
        }

        $sourceCondition = $data['source_condition'];
        $quantity = (int) $data['quantity'];
        $unitPrice = round((float) $data['unit_price'], 2);
        $serialNumbers = $this->serialNumberParser->parse($data['serial_numbers'] ?? '');
        $seriallessQuantity = $product->track_serial_numbers ? (int) ($data['serialless_quantity'] ?? 0) : $quantity;

        $this->validateQuantitySplit($product, $quantity, $serialNumbers, $seriallessQuantity);

        return DB::transaction(function () use ($employee, $product, $warehouse, $data, $userId, $sourceCondition, $quantity, $unitPrice, $serialNumbers, $seriallessQuantity): EmployeeAssetAssignment {
            $assignment = EmployeeAssetAssignment::create([
                'employee_id' => $employee->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'issued_by_user_id' => $userId,
                'source_condition' => $sourceCondition,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
                'serialless_quantity' => $seriallessQuantity,
                'serial_numbers' => $this->formatSerialNumbers($serialNumbers),
                'assigned_at' => $data['assigned_at'],
                'purpose' => $data['purpose'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            if ($sourceCondition === 'new') {
                $this->assignNewStock($assignment, $product, $warehouse, $quantity, $serialNumbers, $seriallessQuantity, $employee);
            } else {
                $this->assignUsedStock($product, $warehouse, $quantity, $serialNumbers, $seriallessQuantity, $employee);
            }

            return $assignment->load(['employee', 'product', 'warehouse']);
        });
    }

    public function returnAsset(EmployeeAssetAssignment $assignment, Warehouse $warehouse, array $data, ?int $userId): EmployeeAssetReturn
    {
        return DB::transaction(function () use ($assignment, $warehouse, $data, $userId): EmployeeAssetReturn {
            $assignment = EmployeeAssetAssignment::query()
                ->with(['product', 'employee', 'returns'])
                ->lockForUpdate()
                ->findOrFail($assignment->id);
            $product = $assignment->product;
            $quantity = (int) $data['quantity'];
            $serialNumbers = $this->serialNumberParser->parse($data['serial_numbers'] ?? '');
            $seriallessQuantity = $product->track_serial_numbers ? (int) ($data['serialless_quantity'] ?? 0) : $quantity;

            $this->validateQuantitySplit($product, $quantity, $serialNumbers, $seriallessQuantity);

            if ($quantity > $assignment->outstandingQuantity()) {
                throw new InvalidArgumentException('Return quantity cannot be greater than the employee outstanding quantity.');
            }

            $assignedSerials = $this->serialNumberParser->parse((string) $assignment->serial_numbers);
            $returnedSerials = $assignment->returns
                ->flatMap(fn (EmployeeAssetReturn $return): array => $this->serialNumberParser->parse((string) $return->serial_numbers))
                ->unique()
                ->values()
                ->all();
            $invalidSerials = array_values(array_diff($serialNumbers, $assignedSerials));
            $duplicateSerials = array_values(array_intersect($serialNumbers, $returnedSerials));

            if ($invalidSerials !== []) {
                throw new InvalidArgumentException('These serials were not assigned to this employee: '.implode(', ', $invalidSerials));
            }

            if ($duplicateSerials !== []) {
                throw new InvalidArgumentException('These serials were already returned: '.implode(', ', $duplicateSerials));
            }

            $returnedSerialless = (int) $assignment->returns->sum('serialless_quantity');

            if ($seriallessQuantity > max(0, $assignment->serialless_quantity - $returnedSerialless)) {
                throw new InvalidArgumentException('Serial-less return quantity exceeds the outstanding serial-less quantity.');
            }

            if ($serialNumbers !== []) {
                $returnableSerials = ProductSerial::query()
                    ->where('product_id', $product->id)
                    ->whereIn('serial_number', $serialNumbers)
                    ->where('status', 'used')
                    ->lockForUpdate()
                    ->pluck('serial_number')
                    ->all();
                $unavailableSerials = array_values(array_diff($serialNumbers, $returnableSerials));

                if ($unavailableSerials !== []) {
                    throw new InvalidArgumentException('These serials are not currently assigned/used: '.implode(', ', $unavailableSerials));
                }
            }

            $return = EmployeeAssetReturn::create([
                'employee_asset_assignment_id' => $assignment->id,
                'warehouse_id' => $warehouse->id,
                'received_by_user_id' => $userId,
                'quantity' => $quantity,
                'serialless_quantity' => $seriallessQuantity,
                'serial_numbers' => $this->formatSerialNumbers($serialNumbers),
                'returned_at' => $data['returned_at'],
                'note' => $data['note'] ?? null,
            ]);

            UsedProductWarehouseStock::query()->firstOrCreate([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
            ], ['quantity' => 0]);
            $usedStock = UsedProductWarehouseStock::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->firstOrFail();
            $usedStock->increment('quantity', $quantity);

            if ($serialNumbers !== []) {
                ProductSerial::query()
                    ->where('product_id', $product->id)
                    ->whereIn('serial_number', $serialNumbers)
                    ->update([
                        'warehouse_id' => $warehouse->id,
                        'status' => 'used_in_stock',
                        'note' => 'Returned by employee '.$assignment->employee->name.'. '.($data['note'] ?? ''),
                    ]);
            }

            return $return->load(['warehouse', 'receivedBy']);
        });
    }

    private function assignNewStock(
        EmployeeAssetAssignment $assignment,
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        array $serialNumbers,
        int $seriallessQuantity,
        Employee $employee,
    ): void {
        $warehouseStock = ProductWarehouseStock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->lockForUpdate()
            ->first();
        $availableQuantity = (int) ($warehouseStock?->quantity ?? 0);

        if ($quantity > $availableQuantity) {
            throw new InvalidArgumentException('Assignment quantity cannot be greater than available new stock.');
        }

        $this->validateAvailableSerials($product, $warehouse, $serialNumbers, 'in_stock');
        $availableSerialCount = ProductSerial::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('status', 'in_stock')
            ->count();

        if ($seriallessQuantity > max(0, $availableQuantity - $availableSerialCount)) {
            throw new InvalidArgumentException('Serial-less quantity exceeds available new serial-less stock.');
        }

        $reason = 'Assigned to employee '.$employee->name.($assignment->purpose ? ' - '.$assignment->purpose : '');
        $this->inventoryService->moveStock(
            $product,
            'use',
            $quantity,
            $reason,
            'IHA-'.$assignment->id,
            $seriallessQuantity,
            $warehouse,
            $serialNumbers,
        );

        $this->markSerialsAssigned($product, $serialNumbers, $employee, $assignment->purpose);
    }

    private function assignUsedStock(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        array $serialNumbers,
        int $seriallessQuantity,
        Employee $employee,
    ): void {
        $usedStock = UsedProductWarehouseStock::query()->firstOrCreate([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
        ], ['quantity' => 0]);
        $usedStock = UsedProductWarehouseStock::query()->lockForUpdate()->findOrFail($usedStock->id);

        if ($quantity > $usedStock->quantity) {
            throw new InvalidArgumentException('Assignment quantity cannot be greater than available used stock.');
        }

        $this->validateAvailableSerials($product, $warehouse, $serialNumbers, 'used_in_stock');
        $availableSerialCount = ProductSerial::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('status', 'used_in_stock')
            ->count();

        if ($seriallessQuantity > max(0, $usedStock->quantity - $availableSerialCount)) {
            throw new InvalidArgumentException('Serial-less quantity exceeds available used serial-less stock.');
        }

        $usedStock->decrement('quantity', $quantity);
        $this->markSerialsAssigned($product, $serialNumbers, $employee, null);
    }

    private function validateAvailableSerials(Product $product, Warehouse $warehouse, array $serialNumbers, string $status): void
    {
        if ($serialNumbers === []) {
            return;
        }

        $availableSerials = ProductSerial::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('status', $status)
            ->whereIn('serial_number', $serialNumbers)
            ->lockForUpdate()
            ->pluck('serial_number')
            ->all();
        $missingSerials = array_values(array_diff($serialNumbers, $availableSerials));

        if ($missingSerials !== []) {
            throw new InvalidArgumentException('These serials are not available in the selected stock: '.implode(', ', $missingSerials));
        }
    }

    private function markSerialsAssigned(Product $product, array $serialNumbers, Employee $employee, ?string $purpose): void
    {
        if ($serialNumbers === []) {
            return;
        }

        ProductSerial::query()
            ->where('product_id', $product->id)
            ->whereIn('serial_number', $serialNumbers)
            ->update([
                'status' => 'used',
                'note' => 'Assigned to employee '.$employee->name.($purpose ? ' - '.$purpose : ''),
            ]);
    }

    private function validateQuantitySplit(Product $product, int $quantity, array $serialNumbers, int $seriallessQuantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        if (! $product->track_serial_numbers && $serialNumbers !== []) {
            throw new InvalidArgumentException('Serial numbers can only be entered for serial-tracked products.');
        }

        if ($product->track_serial_numbers && count($serialNumbers) + $seriallessQuantity !== $quantity) {
            throw new InvalidArgumentException('Serial count plus serial-less quantity must match quantity.');
        }
    }

    private function formatSerialNumbers(array $serialNumbers): ?string
    {
        if ($serialNumbers === []) {
            return null;
        }

        return $this->serialNumberParser->formatCompact(implode(', ', $serialNumbers));
    }
}
