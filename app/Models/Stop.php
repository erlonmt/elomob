<?php

namespace App\Models;

use Database\Factories\StopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A stop, station or bike dock of the network. On PostgreSQL the table also carries the generated
 * PostGIS column "location", which is hidden so its binary representation never reaches JSON.
 *
 * @property float|null $distance_meters Distance from a reference point, set only by proximity queries.
 */
#[Fillable(['name', 'address', 'latitude', 'longitude', 'transport_mode_id', 'accessible', 'tactile_paving', 'visual_signs', 'bikes_available', 'docks_available'])]
#[Hidden(['location'])]
#[WithoutTimestamps]
class Stop extends Model
{
    /** @use HasFactory<StopFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'transport_mode_id' => 'integer',
            'accessible' => 'boolean',
            'tactile_paving' => 'boolean',
            'visual_signs' => 'boolean',
            'bikes_available' => 'integer',
            'docks_available' => 'integer',
        ];
    }

    /**
     * Get the transport mode that primarily serves the stop.
     *
     * @return BelongsTo<TransportMode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class, 'transport_mode_id');
    }
}
