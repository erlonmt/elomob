<?php

namespace App\Models;

use Database\Factories\ItineraryStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One travelled connection of an itinerary. "minutes" already includes "wait_minutes". A step with "fare_integrated"
 * boarded through a fare integration, so "cost_cents" is only the difference up to the higher fare.
 */
#[Fillable(['itinerary_id', 'connection_id', 'sequence', 'minutes', 'transfer_minutes', 'wait_minutes', 'cost_cents', 'fare_integrated', 'distance_meters'])]
#[WithoutTimestamps]
class ItineraryStep extends Model
{
    /** @use HasFactory<ItineraryStepFactory> */
    use HasFactory;

    /**
     * Relations a step needs to be described (see ItineraryStepResource and SpatialNetwork).
     *
     * @var list<string>
     */
    public const array DETAILS = ['connection.mode', 'connection.line', 'connection.fromStop', 'connection.toStop'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'itinerary_id' => 'integer',
            'connection_id' => 'integer',
            'sequence' => 'integer',
            'minutes' => 'integer',
            'transfer_minutes' => 'integer',
            'wait_minutes' => 'integer',
            'cost_cents' => 'integer',
            'fare_integrated' => 'boolean',
            'distance_meters' => 'integer',
        ];
    }

    /**
     * Get the itinerary the step belongs to.
     *
     * @return BelongsTo<Itinerary, $this>
     */
    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    /**
     * Get the travelled connection.
     *
     * @return BelongsTo<Connection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }
}
