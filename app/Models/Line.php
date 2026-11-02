<?php

namespace App\Models;

use Database\Factories\LineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A scheduled public transport line. "starts_at" and "ends_at" are kept as "HH:MM:SS" strings
 * because a service window may cross midnight and is resolved against the trip's departure date.
 */
#[Fillable(['transport_mode_id', 'code', 'name', 'starts_at', 'ends_at', 'frequency_minutes', 'fare_cents'])]
#[WithoutTimestamps]
class Line extends Model
{
    /** @use HasFactory<LineFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transport_mode_id' => 'integer',
            'frequency_minutes' => 'integer',
            'fare_cents' => 'integer',
        ];
    }

    /**
     * Get the transport mode operating the line.
     *
     * @return BelongsTo<TransportMode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class, 'transport_mode_id');
    }
}
