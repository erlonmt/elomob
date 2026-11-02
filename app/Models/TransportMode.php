<?php

namespace App\Models;

use Database\Factories\TransportModeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'color'])]
#[WithoutTimestamps]
class TransportMode extends Model
{
    /** @use HasFactory<TransportModeFactory> */
    use HasFactory;

    /**
     * Get the stops primarily served by this mode.
     *
     * @return HasMany<Stop, $this>
     */
    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class);
    }

    /**
     * Get the scheduled lines operated with this mode.
     *
     * @return HasMany<Line, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(Line::class);
    }
}
