<?php

namespace App\Services\Planning;

use App\Enums\RouteCriterion;

/**
 * Itinerário calculado, ainda não salvo.
 */
final class PlannedRoute
{
    /**
     * @param  list<array{connection_id: int, sequence: int, minutes: int, cost_cents: int, distance_meters: int, wait_minutes: int, transfer_minutes: int, fare_integrated: bool}>  $steps
     */
    public function __construct(
        public readonly RouteCriterion $criterion,
        public readonly int $totalMinutes,
        public readonly int $totalCostCents,
        public readonly int $totalDistanceMeters,
        public readonly int $transfers,
        public readonly int $emissionGrams,
        public readonly array $steps,
    ) {}

    public function itineraryAttributes(): array
    {
        return [
            'criterion' => $this->criterion,
            'total_minutes' => $this->totalMinutes,
            'total_cost_cents' => $this->totalCostCents,
            'total_distance_meters' => $this->totalDistanceMeters,
            'transfers' => $this->transfers,
        ];
    }
}
