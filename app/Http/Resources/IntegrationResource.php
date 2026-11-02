<?php

namespace App\Http\Resources;

use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Requires the "stop", "fromMode" and "toMode" relations to be loaded.
 *
 * @mixin Integration
 */
class IntegrationResource extends JsonResource
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
            'stop_id' => $this->stop_id,
            'from_mode_id' => $this->from_mode_id,
            'to_mode_id' => $this->to_mode_id,
            'transfer_minutes' => $this->transfer_minutes,
            'description' => $this->description,
            'accessible' => $this->accessible,
            'fare_integrated' => $this->fare_integrated,
            'name' => $this->stop->name,
            'latitude' => $this->stop->latitude,
            'longitude' => $this->stop->longitude,
            'from_mode' => $this->fromMode->code,
            'from_name' => $this->fromMode->name,
            'to_mode' => $this->toMode->code,
            'to_name' => $this->toMode->name,
        ];
    }
}
