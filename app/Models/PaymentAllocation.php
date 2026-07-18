<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_by',
        'customer_id',
        'funded_by_customer_id',
        'invoice_id',
        'payment_id',
        'source_type',
        'operation_key',
        'amount',
        'allocated_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'allocated_at' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function fundedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'funded_by_customer_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
