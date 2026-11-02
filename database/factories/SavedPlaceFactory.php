<?php

namespace Database\Factories;

use App\Models\SavedPlace;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedPlace>
 */
class SavedPlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stop_id' => Stop::factory(),
            'label' => fake()->randomElement(['Casa', 'Trabalho', 'Faculdade', 'Academia', 'Casa da vó']).' '.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
