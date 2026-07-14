<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'bill_no',
        'purchase_date',
        'subtotal',
        'note',
        'document_path',
        'document_name',
        'document_mime',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'subtotal' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'party_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseBillItem::class);
    }

    public function versions(): MorphMany
    {
        return $this->morphMany(RecordVersion::class, 'versionable')->latest('id');
    }
}
