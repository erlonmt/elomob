<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Troca de um modal para outro em uma parada, em um sentido.
 */
class Integration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stop_id', 'from_mode_id', 'to_mode_id', 'transfer_minutes', 'description', 'accessible', 'fare_integrated',
    ];

    protected function casts(): array
    {
        return [
            'transfer_minutes' => 'integer',
            'accessible' => 'boolean',
            'fare_integrated' => 'boolean',
        ];
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    public function fromMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class, 'from_mode_id');
    }

    public function toMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class, 'to_mode_id');
    }
}
