<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ItineraryStep */
class ItineraryStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $connection = $this->connection;

        return [
            'sequence' => $this->sequence,
            'mode' => new TransportModeResource($connection->transportMode),
            'line' => $connection->line === null ? null : [
                'id' => $connection->line->id,
                'code' => $connection->line->code,
                'name' => $connection->line->name,
            ],
            'from' => ['id' => $connection->fromStop->id, 'name' => $connection->fromStop->name],
            'to' => ['id' => $connection->toStop->id, 'name' => $connection->toStop->name],
            'minutes' => $this->minutes,
            'ride_minutes' => $this->minutes - $this->wait_minutes - $this->transfer_minutes,
            'wait_minutes' => $this->wait_minutes,
            'transfer_minutes' => $this->transfer_minutes,
            'cost_cents' => $this->cost_cents,
            'fare_integrated' => $this->fare_integrated,
            'distance_meters' => $this->distance_meters,
            'accessibility' => [
                'accessible' => $connection->accessible,
                'has_stairs' => $connection->has_stairs,
                'adapted' => $connection->adapted,
                'audible_signals' => $connection->audible_signals,
                'visual_signs' => $connection->visual_signs,
            ],
            'geometry' => $connection->geometry(),
        ];
    }
}
