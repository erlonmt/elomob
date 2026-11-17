<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Stop */
class StopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'mode' => new TransportModeResource($this->whenLoaded('transportMode')),
            'accessibility' => [
                'accessible' => $this->accessible,
                'tactile_paving' => $this->tactile_paving,
                'visual_signs' => $this->visual_signs,
            ],
            'bikes' => $this->when($this->bikes_available !== null, fn () => [
                'available' => $this->bikes_available,
                'docks' => $this->docks_available,
            ]),
            'distance_meters' => $this->when(isset($this->distance_meters), fn () => (int) $this->distance_meters),
        ];
    }
}
