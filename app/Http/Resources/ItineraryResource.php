<?php

namespace App\Http\Resources;

use App\Models\Itinerary;
use App\Models\ItineraryStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Requires the "steps" relation loaded as described by ItineraryStepResource, in sequence order.
 *
 * @mixin Itinerary
 */
class ItineraryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $steps = $this->steps->values();

        return [
            'id' => $this->id,
            'criterion' => $this->criterion->value,
            'total_minutes' => $this->total_minutes,
            'total_cost_cents' => $this->total_cost_cents,
            'total_distance_meters' => $this->total_distance_meters,
            'transfers' => $this->transfers,
            'steps' => $steps->map(fn (ItineraryStep $step, int $index): ItineraryStepResource => new ItineraryStepResource(
                $step,
                alreadyOnBoard: $steps->has($index - 1) && $steps[$index - 1]->connection->continuesOnBoard($step->connection),
                staysOnBoard: $steps->has($index + 1) && $step->connection->continuesOnBoard($steps[$index + 1]->connection),
            )),
        ];
    }
}
