<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SavedPlace */
class SavedPlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'stop' => new StopResource($this->whenLoaded('stop')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
