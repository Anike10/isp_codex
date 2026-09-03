<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkMapFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_map_id',
        'entry_by',
        'feature_uuid',
        'feature_type',
        'component_type',
        'name',
        'properties',
        'geometry',
        'latitude',
        'longitude',
        'length_meters',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'geometry' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'length_meters' => 'decimal:2',
        ];
    }

    public function networkMap(): BelongsTo
    {
        return $this->belongsTo(NetworkMap::class);
    }

    public function toGeoJsonFeature(): array
    {
        return [
            'type' => 'Feature',
            'id' => $this->feature_uuid,
            'geometry' => $this->geometry,
            'properties' => array_merge($this->properties ?? [], [
                'id' => $this->feature_uuid,
                'feature_type' => $this->feature_type,
                'component_type' => $this->component_type,
                'name' => $this->name,
                'length_meters' => $this->length_meters !== null ? (float) $this->length_meters : null,
                'updated_at' => optional($this->updated_at)->toIso8601String(),
            ]),
        ];
    }
}
