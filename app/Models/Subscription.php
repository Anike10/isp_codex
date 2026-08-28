<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'customer_id',
        'internet_package_id',
        'custom_price',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'custom_price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /** True when this party is billed at a price other than the package list price. */
    public function hasCustomPrice(): bool
    {
        return $this->custom_price !== null;
    }

    /**
     * The monthly price this party is actually billed at: their special price
     * when one is set, otherwise the package's own list price.
     */
    public function effectivePrice(): float
    {
        if ($this->custom_price !== null) {
            return round((float) $this->custom_price, 2);
        }

        $this->loadMissing('package');

        return round((float) ($this->package->monthly_price ?? 0), 2);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'internet_package_id');
    }
}
