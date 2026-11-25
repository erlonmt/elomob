<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stop extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'address', 'latitude', 'longitude', 'transport_mode_id', 'accessible',
        'bikes_available', 'docks_available', 'tactile_paving', 'visual_signs',
    ];

    /** `location` é gerado pelo PostGIS e não serve como JSON. */
    protected $hidden = ['location'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accessible' => 'boolean',
            'tactile_paving' => 'boolean',
            'visual_signs' => 'boolean',
            'bikes_available' => 'integer',
            'docks_available' => 'integer',
        ];
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }

    public function departures(): HasMany
    {
        return $this->hasMany(Connection::class, 'from_stop_id');
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    /** Paradas a até `$meters` metros do ponto, com a distância em `distance_meters`. */
    public function scopeNear(Builder $query, float $latitude, float $longitude, int $meters): Builder
    {
        $point = 'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography';

        return $query
            ->select('stops.*')
            ->selectRaw("round(ST_Distance(location, {$point}))::int AS distance_meters", [$longitude, $latitude])
            ->whereRaw("ST_DWithin(location, {$point}, ?)", [$longitude, $latitude, $meters])
            ->orderBy('distance_meters');
    }
}
