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
}
