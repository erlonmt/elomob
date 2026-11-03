<?php

namespace Tests\Unit;

use App\Services\Routing\Plan;
use App\Services\Routing\RouteLabel;
use PHPUnit\Framework\TestCase;

class PlanTest extends TestCase
{
    public function test_a_plan_keeps_the_totals_and_the_steps_of_the_route_that_arrived(): void
    {
        $origin = new RouteLabel(stop: 1);
        $bus = new RouteLabel(
            stop: 2, service: 'line:7', mode: 'bus', boarded: true, minutes: 20, cost: 380, paidFare: 380, distance: 6000, impact: 4200,
            step: $this->step(11), previous: $origin,
        );
        $metro = new RouteLabel(
            stop: 3, service: 'line:9', mode: 'metro', boarded: true, minutes: 31, cost: 380, paidFare: 380, distance: 9500, transfers: 1, impact: 4800,
            step: $this->step(12), previous: $bus,
        );

        $plan = Plan::fromArrival($metro);

        $this->assertSame([31, 380, 9500, 1, 4800], [$plan->minutes, $plan->cost, $plan->distance, $plan->transfers, $plan->impact]);
        $this->assertSame([11, 12], array_column($plan->steps, 'connection_id'));
        $this->assertSame($metro->steps(), $plan->steps);
    }

    /**
     * @return array{connection_id: int, minutes: int, transfer_minutes: int, wait_minutes: int, cost_cents: int, fare_integrated: bool, distance_meters: int}
     */
    private function step(int $connectionId): array
    {
        return ['connection_id' => $connectionId, 'minutes' => 5, 'transfer_minutes' => 0, 'wait_minutes' => 0, 'cost_cents' => 0, 'fare_integrated' => false, 'distance_meters' => 500];
    }
}
