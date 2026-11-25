<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Line extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transport_mode_id', 'code', 'name', 'starts_at', 'ends_at', 'frequency_minutes', 'fare_cents',
    ];

    protected function casts(): array
    {
        return [
            'frequency_minutes' => 'integer',
            'fare_cents' => 'integer',
        ];
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }
}
