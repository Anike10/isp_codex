<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
