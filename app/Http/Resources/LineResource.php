<?php

namespace App\Http\Resources;

use App\Models\Line;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Requires the "mode" relation to be loaded.
 *
 * @mixin Line
 */
class LineResource extends JsonResource
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
            'transport_mode_id' => $this->transport_mode_id,
            'code' => $this->code,
            'name' => $this->name,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'frequency_minutes' => $this->frequency_minutes,
            'fare_cents' => $this->fare_cents,
            'mode' => $this->mode->code,
            'mode_name' => $this->mode->name,
            'color' => $this->mode->color,
        ];
    }
}
