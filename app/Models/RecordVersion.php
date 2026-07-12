<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RecordVersion extends Model
{
    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'table_name',
        'action',
        'edited_by',
        'edited_by_type',
        'edited_by_name',
        'old_values',
        'new_values',
        'changed_fields',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'changed_fields' => 'array',
            'metadata' => 'array',
        ];
    }

    public function versionable(): MorphTo
    {
        return $this->morphTo();
    }
}
