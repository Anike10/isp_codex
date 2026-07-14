<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'entry_by_type',
        'invoice_id',
        'customer_id',
        'return_no',
        'return_date',
        'subtotal',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'subtotal' => 'decimal:2',
        ];
    }

    public static function generateReturnNo(): string
    {
        $prefix = 'SR-'.now()->format('Y-m-');
        $count = self::query()->where('return_no', 'like', $prefix.'%')->count() + 1;

        do {
            $returnNo = $prefix.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            $count++;
        } while (self::query()->where('return_no', $returnNo)->exists());

        return $returnNo;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
