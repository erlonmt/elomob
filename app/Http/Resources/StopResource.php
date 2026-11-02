<?php

namespace App\Http\Resources;

use App\Models\Stop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Requires the "mode" relation to be loaded.
 *
 * @mixin Stop
 */
class StopResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'transport_mode_id' => $this->transport_mode_id,
            'accessible' => $this->accessible,
            'tactile_paving' => $this->tactile_paving,
            'visual_signs' => $this->visual_signs,
            'bikes_available' => $this->bikes_available,
            'docks_available' => $this->docks_available,
            'mode' => $this->mode->code,
            'mode_name' => $this->mode->name,
            'color' => $this->mode->color,
            'distance_meters' => $this->whenHas('distance_meters'),
        ];
    }
}
