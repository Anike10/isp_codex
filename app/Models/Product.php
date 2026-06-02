<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'name',
        'sku',
        'brand',
        'product_category_id',
        'track_inventory',
        'category',
        'subcategory',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'low_stock_alert',
    ];

    protected $casts = [
        'track_inventory' => 'boolean',
    ];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
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
