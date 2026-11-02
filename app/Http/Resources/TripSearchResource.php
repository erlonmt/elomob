<?php

namespace App\Http\Resources;

use App\Models\TripSearch;
use App\Services\RoutePlanner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Requires the "origin" and "destination" relations; itineraries are included only when loaded.
 *
 * @mixin TripSearch
 */
class TripSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'origin_id' => $this->origin_id,
            'destination_id' => $this->destination_id,
            'origin' => $this->origin->name,
            'destination' => $this->destination->name,
            'created_at' => $this->created_at->toIso8601String(),
            'departure_at' => $this->departure_at?->setTimezone(RoutePlanner::TIMEZONE)->toIso8601String(),
            'filters' => $this->filters,
            'itineraries' => ItineraryResource::collection($this->whenLoaded('itineraries')),
        ];
    }
}
