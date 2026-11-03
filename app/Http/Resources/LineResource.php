<?php

namespace App\Http\Resources;

use App\Models\Line;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Requires the "mode" relation to be loaded. "directions" lists the two ways of the line, each with its stops in
 * order and the minutes from the first one (see LineRoutes).
 *
 * @mixin Line
 */
class LineResource extends JsonResource
{
    /**
     * Create a line resource with the ways the line runs.
     *
     * @param  list<array{to: string, stops: list<array{id: int, name: string, minutes: int}>}>  $directions
     */
    public function __construct(Line $line, private readonly array $directions = [])
    {
        parent::__construct($line);
    }

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
            'directions' => $this->directions,
        ];
    }
}
