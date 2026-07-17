<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OltRefreshRun extends Model
{
    protected $fillable = [
        'olt_device_id',
        'olt_name',
        'refresh_mode',
        'pon_port',
        'status',
        'progress',
        'message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'pon_port' => 'integer',
            'progress' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function oltDevice()
    {
        return $this->belongsTo(OltDevice::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }
}
