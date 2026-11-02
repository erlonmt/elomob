<?php

namespace App\Services;

/**
 * A partial route found by the route planner: the stop it reached, the service it arrived with and its totals so
 * far. A label is immutable and points to the label it extends, so a route keeps no copy of its stops or steps.
 * "paidFare" is the fare already paid for the ride in progress: the highest one of a chain of fare-integrated changes.
 */
final readonly class RouteLabel
{
    /**
     * @param  array{connection_id: int, minutes: int, transfer_minutes: int, wait_minutes: int, cost_cents: int, fare_integrated: bool, distance_meters: int}|null  $step  The step that reached this label, null at the origin.
     */
    public function __construct(
        public int $stop,
        public string $service = '',
        public string $mode = '',
        public bool $boarded = false,
        public int $minutes = 0,
        public int $cost = 0,
        public int $paidFare = 0,
        public int $distance = 0,
        public int $transfers = 0,
        public int $impact = 0,
        public ?array $step = null,
        public ?RouteLabel $previous = null,
    ) {}

    /**
     * Labels compete only with the others at the same stop that arrived with the same service and have boarded or not.
     */
    public function key(): string
    {
        return $this->stop.':'.$this->service.':'.(int) $this->boarded;
    }

    /**
     * Whether the route has already passed through the stop.
     */
    public function hasVisited(int $stop): bool
    {
        for ($label = $this; $label !== null; $label = $label->previous) {
            if ($label->stop === $stop) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether this label is at least as good as the other one on every measure, making the other one useless. Having
     * paid a higher fare for the ride in progress is better, since a fare-integrated change then tops up less.
     */
    public function dominates(RouteLabel $other): bool
    {
        return $this->outranks($other) && $this->paidFare >= $other->paidFare;
    }

    /**
     * Whether this label is at least as good as the other one on every measure the route criteria rank by. When this
     * one has arrived, every route extending the other one is at best as good, since going further only adds up.
     */
    public function outranks(RouteLabel $other): bool
    {
        return $this->minutes <= $other->minutes
            && $this->cost <= $other->cost
            && $this->transfers <= $other->transfers
            && $this->impact <= $other->impact;
    }

    /**
     * The measures the route criteria rank routes by.
     *
     * @return array{minutes: int, cost: int, transfers: int, impact: int}
     */
    public function measures(): array
    {
        return ['minutes' => $this->minutes, 'cost' => $this->cost, 'transfers' => $this->transfers, 'impact' => $this->impact];
    }

    /**
     * The steps of the route, in travel order.
     *
     * @return list<array{connection_id: int, minutes: int, transfer_minutes: int, wait_minutes: int, cost_cents: int, fare_integrated: bool, distance_meters: int}>
     */
    public function steps(): array
    {
        $steps = [];
        for ($label = $this; $label->step !== null; $label = $label->previous) {
            $steps[] = $label->step;
        }

        return array_reverse($steps);
    }
}
