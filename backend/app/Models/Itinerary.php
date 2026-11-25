<?php

namespace App\Models;

use App\Enums\RouteCriterion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Itinerary extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'criterion', 'total_minutes', 'total_cost_cents', 'total_distance_meters', 'transfers',
    ];

    protected function casts(): array
    {
        return [
            'criterion' => RouteCriterion::class,
            'total_minutes' => 'integer',
            'total_cost_cents' => 'integer',
            'total_distance_meters' => 'integer',
            'transfers' => 'integer',
        ];
    }

    public function tripSearch(): BelongsTo
    {
        return $this->belongsTo(TripSearch::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ItineraryStep::class)->orderBy('sequence');
    }
}
