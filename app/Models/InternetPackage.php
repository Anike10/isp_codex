<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InternetPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'name',
        'speed',
        'mikrotik_profile',
        'monthly_price',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function versions(): MorphMany
    {
        return $this->morphMany(RecordVersion::class, 'versionable')->latest('id');
    }
}
