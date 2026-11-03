<?php

namespace App\Http\Resources;

use App\Models\Itinerary;
use App\Services\Routing\RoutePlanner;
use Illuminate\Http\Request;

/**
 * An itinerary with its search summary and its GeoJSON for the map, as shown on the itinerary detail screen. A search
 * to leave now has no departure; its creation time is when the trip starts.
 * Requires the "tripSearch" relation with its "origin" and "destination", plus the described "steps".
 *
 * @mixin Itinerary
 */
class ItineraryDetailResource extends ItineraryResource
{
    /**
     * @param  array{type: string, features: list<array<string, mixed>>}  $geojson
     */
    public function __construct(Itinerary $itinerary, private array $geojson)
    {
        parent::__construct($itinerary);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $search = $this->tripSearch;

        return parent::toArray($request) + [
            'trip_search_id' => $search->id,
            'search' => [
                'id' => $search->id,
                'origin_id' => $search->origin_id,
                'destination_id' => $search->destination_id,
                'origin' => $search->origin->name,
                'destination' => $search->destination->name,
                'departure_at' => $search->departure_at?->setTimezone(RoutePlanner::TIMEZONE)->toIso8601String(),
                'created_at' => $search->created_at->toIso8601String(),
                'filters' => $search->filters,
            ],
            'geojson' => $this->geojson,
        ];
    }
}
