<?php

namespace Database\Factories;

use App\Enums\TransportModeCode;
use App\Models\Stop;
use App\Models\TripSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TripSearch>
 */
class TripSearchFactory extends Factory
{
    /**
     * Define the model's default state: a search made by a signed-in user between two new stops.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_hash' => TripSearch::hashSessionKey((string) Str::uuid()),
            'origin_id' => Stop::factory(),
            'destination_id' => Stop::factory(),
            'filters' => [
                'modes' => [TransportModeCode::Bus->value, TransportModeCode::Metro->value],
                'accessibility_needs' => [],
            ],
        ];
    }

    /**
     * Indicate that a guest made the search from the session holding the given key.
     */
    public function forGuestSession(string $sessionKey): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
            'session_hash' => TripSearch::hashSessionKey($sessionKey),
        ]);
    }
}
