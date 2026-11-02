<?php

namespace App\Http\Resources;

use App\Enums\AccessibilityNeed;
use App\Models\ItineraryStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Requires the "connection" relation with its "mode", "line", "fromStop" and "toStop" relations. "accessible_for"
 * lists the accessibility needs the step meets, leaving out the integration it may start with and the stops the
 * passenger stays on board at, which ItineraryResource tells from the steps around it.
 *
 * @mixin ItineraryStep
 */
class ItineraryStepResource extends JsonResource
{
    /**
     * Create a step resource, saying whether the passenger was already on board at its first stop and stays on
     * board at its last one.
     */
    public function __construct(ItineraryStep $step, private readonly bool $alreadyOnBoard = false, private readonly bool $staysOnBoard = false)
    {
        parent::__construct($step);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $connection = $this->resource->connection;

        return [
            'id' => $this->id,
            'itinerary_id' => $this->itinerary_id,
            'connection_id' => $this->connection_id,
            'sequence' => $this->sequence,
            'minutes' => $this->minutes,
            'cost_cents' => $this->cost_cents,
            'fare_integrated' => $this->fare_integrated,
            'distance_meters' => $this->distance_meters,
            'transfer_minutes' => $this->transfer_minutes,
            'wait_minutes' => $this->wait_minutes,
            'mode' => $connection->mode->code,
            'mode_name' => $connection->mode->name,
            'color' => $connection->mode->color,
            'origin_id' => $connection->from_stop_id,
            'destination_id' => $connection->to_stop_id,
            'origin' => $connection->fromStop->name,
            'destination' => $connection->toStop->name,
            'origin_latitude' => $connection->fromStop->latitude,
            'origin_longitude' => $connection->fromStop->longitude,
            'destination_latitude' => $connection->toStop->latitude,
            'destination_longitude' => $connection->toStop->longitude,
            'line_code' => $connection->line?->code,
            'has_stairs' => $connection->has_stairs,
            'accessible_for' => array_values(array_map(
                fn (AccessibilityNeed $need): string => $need->value,
                array_filter(AccessibilityNeed::cases(), fn (AccessibilityNeed $need): bool => $need->isMetBy($connection, $this->alreadyOnBoard, $this->staysOnBoard)),
            )),
        ];
    }
}
