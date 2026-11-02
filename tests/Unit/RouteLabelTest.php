<?php

namespace Tests\Unit;

use App\Services\RouteLabel;
use PHPUnit\Framework\TestCase;

class RouteLabelTest extends TestCase
{
    public function test_a_route_lists_its_steps_in_travel_order_and_knows_every_stop_it_passed(): void
    {
        $origin = new RouteLabel(stop: 1);
        $bus = new RouteLabel(stop: 2, service: 'line:7', mode: 'bus', boarded: true, minutes: 20, step: $this->step(11), previous: $origin);
        $walk = new RouteLabel(stop: 3, service: 'walk', mode: 'walk', boarded: true, minutes: 27, step: $this->step(12), previous: $bus);

        $this->assertSame([], $origin->steps());
        $this->assertSame([11, 12], array_column($walk->steps(), 'connection_id'));
        $this->assertTrue($walk->hasVisited(1));
        $this->assertTrue($walk->hasVisited(2));
        $this->assertTrue($walk->hasVisited(3));
        $this->assertFalse($walk->hasVisited(4));
        $this->assertFalse($bus->hasVisited(3));
        $this->assertSame('2:line:7:1', $bus->key());
        $this->assertSame('1::0', $origin->key());
    }

    public function test_rides_of_the_same_hired_mode_ending_at_a_stop_compete_with_each_other(): void
    {
        $taxi = new RouteLabel(stop: 5, service: 'taxi:40', mode: 'taxi', boarded: true);

        $this->assertSame($taxi->key(), (new RouteLabel(stop: 5, service: 'taxi:41', mode: 'taxi', boarded: true))->key());
        $this->assertNotSame($taxi->key(), (new RouteLabel(stop: 5, service: 'app:42', mode: 'app', boarded: true))->key());
    }

    public function test_a_label_dominates_another_only_when_no_measure_is_worse_and_it_paid_at_least_as_much(): void
    {
        $label = fn (int $minutes, int $cost, int $paidFare, int $transfers, int $impact): RouteLabel => new RouteLabel(
            stop: 2, service: 'line:7', mode: 'bus', boarded: true, minutes: $minutes, cost: $cost, paidFare: $paidFare, transfers: $transfers, impact: $impact,
        );
        $route = $label(30, 380, 380, 1, 100);

        $this->assertTrue($route->dominates($label(30, 380, 380, 1, 100)));
        $this->assertTrue($route->dominates($label(31, 400, 380, 2, 120)));
        $this->assertFalse($route->dominates($label(29, 400, 380, 2, 120)));
        $this->assertFalse($route->dominates($label(31, 379, 380, 2, 120)));
        $this->assertFalse($route->dominates($label(31, 400, 380, 0, 120)));
        $this->assertFalse($route->dominates($label(31, 400, 380, 2, 99)));
        $this->assertFalse($route->dominates($label(31, 400, 381, 2, 120)));
        $this->assertSame(['minutes' => 30, 'cost' => 380, 'transfers' => 1, 'impact' => 100], $route->measures());
    }

    public function test_an_arrival_outranks_a_partial_route_on_the_ranked_measures_whatever_fare_the_route_paid(): void
    {
        $arrival = new RouteLabel(stop: 9, minutes: 40, cost: 380, paidFare: 160, transfers: 1, impact: 500);

        $this->assertTrue($arrival->outranks(new RouteLabel(stop: 4, minutes: 40, cost: 380, paidFare: 450, transfers: 1, impact: 500)));
        $this->assertFalse($arrival->outranks(new RouteLabel(stop: 4, minutes: 39, cost: 450, paidFare: 450, transfers: 2, impact: 900)));
        $this->assertFalse($arrival->outranks(new RouteLabel(stop: 4, minutes: 45, cost: 379, transfers: 2, impact: 900)));
        $this->assertFalse($arrival->outranks(new RouteLabel(stop: 4, minutes: 45, cost: 450, transfers: 0, impact: 900)));
        $this->assertFalse($arrival->outranks(new RouteLabel(stop: 4, minutes: 45, cost: 450, transfers: 2, impact: 499)));
    }

    /**
     * @return array{connection_id: int, minutes: int, transfer_minutes: int, wait_minutes: int, cost_cents: int, fare_integrated: bool, distance_meters: int}
     */
    private function step(int $connectionId): array
    {
        return ['connection_id' => $connectionId, 'minutes' => 5, 'transfer_minutes' => 0, 'wait_minutes' => 0, 'cost_cents' => 0, 'fare_integrated' => false, 'distance_meters' => 500];
    }
}
