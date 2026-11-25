<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TripSearch */
class TripSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'origin' => new StopResource($this->whenLoaded('origin')),
            'destination' => new StopResource($this->whenLoaded('destination')),
            'departure_at' => $this->departure_at?->toIso8601String(),
            'filters' => $this->filters,
            'created_at' => $this->created_at?->toIso8601String(),
            'itineraries' => ItineraryResource::collection($this->whenLoaded('itineraries')),
        ];
    }
}
