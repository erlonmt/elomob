<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Line */
class LineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'mode' => new TransportModeResource($this->whenLoaded('transportMode')),
            'starts_at' => substr($this->starts_at, 0, 5),
            'ends_at' => substr($this->ends_at, 0, 5),
            'frequency_minutes' => $this->frequency_minutes,
            'fare_cents' => $this->fare_cents,
            // Paradas atendidas, na ordem em que aparecem nos trechos da linha.
            'stops' => $this->whenLoaded('connections', fn () => StopResource::collection(
                $this->connections->flatMap(fn ($c) => [$c->fromStop, $c->toStop])->unique('id')->values()
            )),
        ];
    }
}
