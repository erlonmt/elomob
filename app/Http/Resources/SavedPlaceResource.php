<?php

namespace App\Http\Resources;

use App\Models\SavedPlace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Requires the "stop" relation to be loaded.
 *
 * @mixin SavedPlace
 */
class SavedPlaceResource extends JsonResource
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
            'label' => $this->label,
            'stop_id' => $this->stop_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'name' => $this->stop->name,
            'address' => $this->stop->address,
            'latitude' => $this->stop->latitude,
            'longitude' => $this->stop->longitude,
        ];
    }
}
