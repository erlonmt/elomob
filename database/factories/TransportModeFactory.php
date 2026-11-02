<?php

namespace Database\Factories;

use App\Models\TransportMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransportMode>
 */
class TransportModeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('modo-####'),
            'name' => fake()->randomElement(['BRT', 'VLT', 'Catamarã', 'Barco', 'Patinete']),
            'color' => fake()->hexColor(),
        ];
    }
}
