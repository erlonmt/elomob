<?php

namespace Database\Factories;

use App\Enums\RouteCriterion;
use App\Models\Itinerary;
use App\Models\TripSearch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Itinerary>
 */
class ItineraryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_search_id' => TripSearch::factory(),
            'total_minutes' => fake()->numberBetween(10, 90),
            'total_cost_cents' => fake()->randomElement([0, 430, 590, 860]),
            'total_distance_meters' => fake()->numberBetween(1000, 15000),
            'transfers' => fake()->numberBetween(0, 2),
        ];
    }

    /**
     * Give each itinerary a criterion its search does not have yet, also within one batch (count()).
     */
    public function configure(): static
    {
        return $this->sequence(function (Sequence $sequence): array {
            $positionInBatch = $sequence->index;

            return ['criterion' => function (array $attributes) use ($positionInBatch): RouteCriterion {
                $taken = Itinerary::query()->where('trip_search_id', $attributes['trip_search_id'])->pluck('criterion');

                return collect(RouteCriterion::cases())
                    ->reject(fn (RouteCriterion $criterion): bool => $taken->containsStrict($criterion))
                    ->values()->get($positionInBatch, RouteCriterion::Fastest);
            }];
        });
    }

    /**
     * Indicate the ranking criterion the itinerary was optimized for.
     */
    public function optimizedFor(RouteCriterion $criterion): static
    {
        return $this->state(fn (array $attributes): array => [
            'criterion' => $criterion,
        ]);
    }
}
