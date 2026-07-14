<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'name',
        'sku',
        'barcode',
        'brand',
        'product_category_id',
        'product_type',
        'track_inventory',
        'track_serial_numbers',
        'warranty_days',
        'service_guarantee_days',
        'category',
        'subcategory',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'low_stock_alert',
    ];

    protected $casts = [
        'track_inventory' => 'boolean',
        'track_serial_numbers' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (Product $product): void {
            $product->refresh();

            if (! $product->track_inventory || (int) $product->stock_quantity <= 0) {
                return;
            }

            $defaultWarehouseId = Warehouse::query()->where('is_default', true)->value('id');

            if ($defaultWarehouseId) {
                $product->warehouseStocks()->firstOrCreate(
                    ['warehouse_id' => $defaultWarehouseId],
                    ['quantity' => (int) $product->stock_quantity],
                );
            }
        });
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }

    public function usedWarehouseStocks(): HasMany
    {
        return $this->hasMany(UsedProductWarehouseStock::class);
    }

    public function employeeAssetAssignments(): HasMany
    {
        return $this->hasMany(EmployeeAssetAssignment::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }

    public function isService(): bool
    {
        return in_array($this->product_type, ['service', 'warranty'], true) || ! $this->track_inventory;
    }

    public function categoryIdPath(): array
    {
        $ids = [];
        $category = $this->productCategory;

        while ($category) {
            array_unshift($ids, $category->id);
            $category = $category->parent;
        }

        return $ids;
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory && $this->stock_quantity <= $this->low_stock_alert;
    }
}
