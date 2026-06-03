<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarrantyClaim extends Model
{
    use HasFactory;

    public const OPEN_STATUSES = [
        'pending',
        'received',
        'diagnosing',
        'repairing',
        'sent_to_vendor',
        'vendor_returned',
        'ready',
        'paid_service',
    ];

    protected $fillable = [
        'claim_no',
        'customer_id',
        'invoice_id',
        'invoice_item_id',
        'product_id',
        'product_serial_id',
        'claim_date',
        'received_at',
        'closed_at',
        'warranty_status',
        'problem_description',
        'diagnosis_note',
        'action_type',
        'status',
        'assigned_to',
        'vendor_id',
        'replacement_product_id',
        'replacement_product_serial_id',
        'service_invoice_id',
        'service_charge',
        'resolution_note',
        'delivery_note',
        'entry_by',
        'entry_by_type',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'received_at' => 'datetime',
            'closed_at' => 'datetime',
            'service_charge' => 'decimal:2',
        ];
    }

    public static function nextClaimNo(): string
    {
        $prefix = 'WC-'.now()->format('Ymd').'-';
        $count = self::query()->where('claim_no', 'like', $prefix.'%')->count() + 1;

        do {
            $claimNo = $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (self::query()->where('claim_no', $claimNo)->exists());

        return $claimNo;
    }

    public static function warrantyStatusFor(?ProductSerial $serial): string
    {
        if (! $serial) {
            return 'unknown';
        }

        if (! $serial->warranty_until) {
            return 'no_warranty';
        }

        return $serial->warranty_until->copy()->endOfDay()->gte(now()) ? 'in_warranty' : 'expired';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productSerial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'vendor_id');
    }

    public function replacementProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'replacement_product_id');
    }

    public function replacementProductSerial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'replacement_product_serial_id');
    }

    public function serviceInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'service_invoice_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WarrantyClaimLog::class);
    }
}
