<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyClaimLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'old_status',
        'new_status',
        'note',
        'entry_by',
        'entry_by_type',
    ];

    public function warrantyClaim(): BelongsTo
    {
        return $this->belongsTo(WarrantyClaim::class);
    }
}
