<?php

namespace App\Http\Resources;

use App\Services\Planning\EmissionCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Itinerary */
class ItineraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'criterion' => $this->criterion->value,
            'label' => $this->criterion->label(),
            'total_minutes' => $this->total_minutes,
            'total_cost_cents' => $this->total_cost_cents,
            'total_distance_meters' => $this->total_distance_meters,
            'transfers' => $this->transfers,
            'emission_grams' => $this->steps->sum(
                fn ($step) => EmissionCalculator::grams($step->connection->transportMode->code, $step->distance_meters)
            ),
            'steps' => ItineraryStepResource::collection($this->steps),
        ];
    }
}
