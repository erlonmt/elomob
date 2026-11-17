<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trecho dirigido entre duas paradas. Com linha, cobra a tarifa da linha; sem
 * linha (táxi, aplicativo, bicicleta, caminhada), cobra `boarding_fare_cents`.
 */
class Connection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'from_stop_id', 'to_stop_id', 'transport_mode_id', 'line_id', 'minutes', 'distance_meters',
        'boarding_fare_cents', 'accessible', 'has_stairs', 'adapted', 'audible_signals', 'visual_signs',
    ];

    /** `path` é binário; use o escopo withGeoJson para obter a geometria. */
    protected $hidden = ['path', 'path_geojson'];

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'distance_meters' => 'integer',
            'boarding_fare_cents' => 'integer',
            'accessible' => 'boolean',
            'has_stairs' => 'boolean',
            'adapted' => 'boolean',
            'audible_signals' => 'boolean',
            'visual_signs' => 'boolean',
        ];
    }

    public function fromStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'from_stop_id');
    }

    public function toStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'to_stop_id');
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }

    public function scopeWithGeoJson(Builder $query): Builder
    {
        return $query->select('connections.*')->selectRaw('ST_AsGeoJSON(path) AS path_geojson');
    }

    /** Geometria GeoJSON do trecho, quando carregada pelo escopo withGeoJson. */
    public function geometry(): ?array
    {
        $json = $this->getAttribute('path_geojson');

        return $json === null ? null : json_decode($json, true);
    }
}
