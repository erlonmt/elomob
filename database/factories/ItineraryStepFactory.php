<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Itinerary;
use App\Models\ItineraryStep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<ItineraryStep>
 */
class ItineraryStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $waitMinutes = fake()->numberBetween(0, 15);

        return [
            'itinerary_id' => Itinerary::factory(),
            'connection_id' => Connection::factory(),
            'minutes' => fake()->numberBetween(2, 40) + $waitMinutes,
            'transfer_minutes' => 0,
            'wait_minutes' => $waitMinutes,
            'cost_cents' => fake()->randomElement([0, 430]),
            'fare_integrated' => false,
            'distance_meters' => fake()->numberBetween(200, 9000),
        ];
    }

    /**
     * Number steps after the ones the itinerary already has, also within one batch (count()).
     */
    public function configure(): static
    {
        return $this->sequence(function (Sequence $sequence): array {
            $positionInBatch = $sequence->index;

            return ['sequence' => fn (array $attributes): int => (int) ItineraryStep::query()
                ->where('itinerary_id', $attributes['itinerary_id'])->max('sequence') + $positionInBatch + 1];
        });
    }
}
