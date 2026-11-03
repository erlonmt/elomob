<?php

namespace App\Enums;

/**
 * Ranking criteria a search is planned for, in the order the itineraries are produced. The public transport option
 * is the fastest trip only by bus and metro (and on foot), offered only when no other criterion chose such a trip.
 */
enum RouteCriterion: string
{
    case Fastest = 'fastest';
    case Cheapest = 'cheapest';
    case Transfers = 'transfers';
    case Sustainable = 'sustainable';
    case PublicTransport = 'public_transport';

    /**
     * The criteria a profile may prefer: all but the public transport option, which only fills a gap in the others.
     *
     * @return list<self>
     */
    public static function preferable(): array
    {
        return [self::Fastest, self::Cheapest, self::Transfers, self::Sustainable];
    }

    /**
     * Sort key of a route's measures for this criterion: the measure it optimizes first, then its tie-breakers.
     *
     * @param  array{minutes: int, cost: int, transfers: int, impact: int}  $measures
     * @return array{int, int, int}
     */
    public function sortKey(array $measures): array
    {
        return match ($this) {
            self::Fastest, self::PublicTransport => [$measures['minutes'], $measures['cost'], $measures['transfers']],
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
