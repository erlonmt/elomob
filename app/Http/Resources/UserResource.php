<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'preferred_route' => $this->preferred_route->value,
            'transport_modes' => $this->transport_modes,
            'accessibility_needs' => $this->accessibility_needs,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
