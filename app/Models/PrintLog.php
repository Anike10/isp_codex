<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PrintLog extends Model
{
    protected $fillable = ['organization_id', 'printable_type', 'printable_id', 'document_type', 'document_no', 'user_id', 'user_name', 'printed_at', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['printed_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function printable(): MorphTo { return $this->morphTo(); }
}
