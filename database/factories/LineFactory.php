<?php

namespace Database\Factories;

use App\Models\Line;
use App\Models\TransportMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Line>
 */
class LineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = fake()->randomElement(['Boa Viagem', 'Candeias', 'Casa Amarela', 'Macaxeira', 'Barro', 'Jardim São Paulo']);
        $to = fake()->randomElement(['Derby', 'Conde da Boa Vista', 'Cais de Santa Rita', 'TI Joana Bezerra']);

        return [
            'transport_mode_id' => TransportMode::factory(),
            'code' => fake()->unique()->numerify('9###'),
            'name' => "{$from} → {$to}",
            'starts_at' => '05:00:00',
            'ends_at' => '23:00:00',
            'frequency_minutes' => fake()->numberBetween(8, 30),
            'fare_cents' => 430,
        ];
    }
}
