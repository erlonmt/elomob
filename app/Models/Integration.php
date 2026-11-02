<?php

namespace App\Models;

use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A transfer between two modes at a stop: the time it takes, whether it is step-free and whether it integrates the
 * fare, so that boarding there charges only the difference up to the higher fare.
 */
#[Fillable(['stop_id', 'from_mode_id', 'to_mode_id', 'transfer_minutes', 'description', 'accessible', 'fare_integrated'])]
#[WithoutTimestamps]
class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stop_id' => 'integer',
            'from_mode_id' => 'integer',
            'to_mode_id' => 'integer',
            'transfer_minutes' => 'integer',
            'accessible' => 'boolean',
            'fare_integrated' => 'boolean',
        ];
    }

    /**
     * Get the stop where the transfer happens.
     *
     * @return BelongsTo<Stop, $this>
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    /**
     * Get the mode the passenger arrives with.
     *
     * @return BelongsTo<TransportMode, $this>
     */
    public function fromMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class, 'from_mode_id');
    }

    /**
     * Get the mode the passenger continues with.
     *
     * @return BelongsTo<TransportMode, $this>
     */
    public function toMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class, 'to_mode_id');
    }
}
