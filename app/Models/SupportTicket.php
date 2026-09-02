<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUSES = ['open', 'processing', 'resolved', 'closed'];

    protected $fillable = [
        'entry_by',
        'customer_id',
        'assigned_to',
        'subject',
        'description',
        'priority',
        'status',
        'rx_power_on_create',
        'rx_power_on_update',
        'rx_power_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'rx_power_on_create' => 'decimal:2',
            'rx_power_on_update' => 'decimal:2',
            'rx_power_updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }
}
