<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\Stop;
use App\Models\TransportMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stop_id' => Stop::factory(),
            'from_mode_id' => TransportMode::factory(),
            'to_mode_id' => TransportMode::factory(),
            'transfer_minutes' => fake()->numberBetween(1, 10),
            'description' => 'Integração no '.fake()->randomElement(['TI Macaxeira', 'TI Barro', 'TI Aeroporto', 'TI Tancredo Neves', 'TI Camaragibe']),
            'accessible' => true,
            'fare_integrated' => false,
        ];
    }

    /**
     * Indicate that changing modes here integrates the fare.
     */
    public function fareIntegrated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'fare_integrated' => true,
        ]);
    }
}
