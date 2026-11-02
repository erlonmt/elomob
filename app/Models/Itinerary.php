<?php

namespace App\Models;

use App\Enums\RouteCriterion;
use Database\Factories\ItineraryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['trip_search_id', 'criterion', 'total_minutes', 'total_cost_cents', 'total_distance_meters', 'transfers'])]
#[WithoutTimestamps]
class Itinerary extends Model
{
    /** @use HasFactory<ItineraryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trip_search_id' => 'integer',
            'criterion' => RouteCriterion::class,
            'total_minutes' => 'integer',
            'total_cost_cents' => 'integer',
            'total_distance_meters' => 'integer',
            'transfers' => 'integer',
        ];
    }

    /**
     * Get the search that produced the itinerary.
     *
     * @return BelongsTo<TripSearch, $this>
     */
    public function tripSearch(): BelongsTo
    {
        return $this->belongsTo(TripSearch::class);
    }

    /**
     * Get the steps of the itinerary in travel order.
     *
     * @return HasMany<ItineraryStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ItineraryStep::class)->orderBy('sequence');
    }
}
