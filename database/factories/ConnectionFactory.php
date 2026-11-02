<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Line;
use App\Models\Stop;
use App\Models\TransportMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Connection>
 */
class ConnectionFactory extends Factory
{
    /**
     * Define the model's default state. Each side gets its own new stop, so both ends always differ.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_stop_id' => Stop::factory(),
            'to_stop_id' => Stop::factory(),
            'transport_mode_id' => TransportMode::factory(),
            'line_id' => null,
            'minutes' => fake()->numberBetween(2, 40),
            'distance_meters' => fake()->numberBetween(200, 9000),
            'boarding_fare_cents' => 0,
            'accessible' => true,
            'has_stairs' => false,
            'adapted' => true,
            'audible_signals' => true,
            'visual_signs' => true,
        ];
    }

    /**
     * Indicate that the connection is served by the given line, with the line's mode and fare (it stores no fare of its own).
     */
    public function onLine(Line $line): static
    {
        return $this->state(fn (array $attributes): array => [
            'line_id' => $line->id,
            'transport_mode_id' => $line->transport_mode_id,
            'boarding_fare_cents' => null,
        ]);
    }

    /**
     * Indicate that the connection has stairs and is not accessible.
     */
    public function withStairs(): static
    {
        return $this->state(fn (array $attributes): array => [
            'accessible' => false,
            'has_stairs' => true,
        ]);
    }
}
