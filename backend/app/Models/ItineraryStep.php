<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Etapa de um itinerário. `minutes` já inclui a espera e o tempo de integração.
 */
class ItineraryStep extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'connection_id', 'sequence', 'minutes', 'cost_cents', 'distance_meters',
        'wait_minutes', 'transfer_minutes', 'fare_integrated',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'minutes' => 'integer',
            'cost_cents' => 'integer',
            'distance_meters' => 'integer',
            'wait_minutes' => 'integer',
            'transfer_minutes' => 'integer',
            'fare_integrated' => 'boolean',
        ];
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }
}
