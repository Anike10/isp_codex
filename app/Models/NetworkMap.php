<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'customer_id',
        'is_test',
        'is_default',
        'entry_by',
    ];

    protected function casts(): array
    {
        return [
            'is_test' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(NetworkMapFeature::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
