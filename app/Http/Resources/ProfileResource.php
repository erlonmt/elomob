<?php

namespace App\Http\Resources;

use App\Enums\RouteCriterion;
use App\Enums\TransportModeCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class ProfileResource extends JsonResource
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
            'email' => $this->email,
            'preferred_route' => ($this->preferred_route ?? RouteCriterion::Fastest)->value,
            'transport_modes' => $this->transport_modes ?? TransportModeCode::selectableValues(),
            'accessibility_needs' => $this->accessibility_needs ?? [],
        ];
    }
}
