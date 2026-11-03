<?php

namespace App\Services\Routing;

/**
 * The route the route planner chose for one criterion: its totals and the steps to take, in travel order. "impact" is
 * the environmental impact the most sustainable route is ranked by; it is not stored with the itinerary.
 */
final readonly class Plan
{
    /**
     * @param  list<array{connection_id: int, minutes: int, transfer_minutes: int, wait_minutes: int, cost_cents: int, fare_integrated: bool, distance_meters: int}>  $steps
     */
    public function __construct(
        public int $minutes,
        public int $cost,
        public int $distance,
        public int $transfers,
        public int $impact,
        public array $steps,
    ) {}

    /**
     * Get the plan of a route that reached the destination.
     */
    public static function fromArrival(RouteLabel $arrival): self
    {
        return new self(
            minutes: $arrival->minutes,
            cost: $arrival->cost,
            distance: $arrival->distance,
            transfers: $arrival->transfers,
            impact: $arrival->impact,
            steps: $arrival->steps(),
        );
    }
}
