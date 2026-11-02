<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The visitor's session as the frontend needs it: the signed-in account (null for a guest) and the CSRF token
 * to send back with every change.
 *
 * @property-read User|null $resource
 */
class SessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{user: ProfileResource|null, csrf_token: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => $this->resource ? new ProfileResource($this->resource) : null,
            'csrf_token' => $request->session()->token(),
        ];
    }
}
