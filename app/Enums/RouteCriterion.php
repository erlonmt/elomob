<?php

namespace App\Enums;

/**
 * Ranking criteria a search is planned for, in the order the itineraries are produced.
 */
enum RouteCriterion: string
{
    case Fastest = 'fastest';
    case Cheapest = 'cheapest';
    case Transfers = 'transfers';
    case Sustainable = 'sustainable';

    /**
     * Sort key of a route's measures for this criterion: the measure it optimizes first, then its tie-breakers.
     *
     * @param  array{minutes: int, cost: int, transfers: int, impact: int}  $measures
     * @return array{int, int, int}
     */
    public function sortKey(array $measures): array
    {
        return match ($this) {
            self::Fastest => [$measures['minutes'], $measures['cost'], $measures['transfers']],
            self::Cheapest => [$measures['cost'], $measures['minutes'], $measures['transfers']],
            self::Transfers => [$measures['transfers'], $measures['minutes'], $measures['cost']],
            self::Sustainable => [$measures['impact'], $measures['minutes'], $measures['cost']],
        };
    }

    /**
     * Whether this criterion may choose a route taking the given minutes when the fastest one takes $fastestMinutes.
     * The most sustainable route takes at most a quarter longer than the fastest, so a lower impact never costs a
     * much longer trip; the other criteria take any route. The planner measures against the fastest route without a
     * taxi or app ride, since a ride would leave out every bus, metro and bike route.
     */
    public function accepts(int $minutes, int $fastestMinutes): bool
    {
        return $this !== self::Sustainable || $minutes * 4 <= $fastestMinutes * 5;
    }
}
