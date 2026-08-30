<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOnuPowerSample extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'olt_onu_id',
        'rx_power_dbm',
        'tx_power_dbm',
        'status',
        'sampled_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'rx_power_dbm' => 'decimal:2',
            'tx_power_dbm' => 'decimal:2',
            'sampled_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function oltOnu(): BelongsTo
    {
        return $this->belongsTo(OltOnu::class);
    }
}
