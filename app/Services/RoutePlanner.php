<?php

namespace App\Services;

use App\Enums\AccessibilityNeed;
use App\Enums\RouteCriterion;
use App\Enums\TransportModeCode;
use App\Models\Connection;
use App\Models\Stop;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class RoutePlanner
{
    /**
     * Time zone of the network: line schedules, and departures requested without an offset, are read in it.
     */
    public const string TIMEZONE = 'America/Recife';

    public function __construct(private MobilityNetwork $network) {}

    /**
     * Find, for each criterion (fastest, cheapest, transfers, sustainable), the best path from origin to
     * destination departing at "departure_at" (default: now in Recife). A single RouteSearch finds every route no
     * other one beats on all measures, and each criterion picks its best among the ones it accepts (the most
     * sustainable takes at most a quarter longer than the fastest route without a taxi or app ride, or than the
     * fastest route when every one takes a ride); the plans are keyed by the
     * criterion value, in RouteCriterion order, and criteria without a path are omitted. Each accessibility need
     * keeps only the connections offering what it requires and the stops doing so wherever the passenger is at them
     * (every stop but those passed on board), and wheelchair and reduced mobility needs also avoid inaccessible
     * integrations.
     *
     * @param  array{modes: array<int, value-of<TransportModeCode>>, departure_at?: ?string, accessibility_needs?: list<value-of<AccessibilityNeed>>}  $filters
     * @return array<value-of<RouteCriterion>, array{minutes: int, cost: int, distance: int, transfers: int, impact: int, steps: list<array{connection_id: int, minutes: int, transfer_minutes: int, wait_minutes: int, cost_cents: int, fare_integrated: bool, distance_meters: int}>}>
     */
    public function plan(int $origin, int $destination, array $filters): array
    {
        $needs = array_map(AccessibilityNeed::from(...), $filters['accessibility_needs'] ?? []);
        $network = $this->network->load([...$filters['modes'], TransportModeCode::Walk->value]);
        $search = new RouteSearch(
            connectionsFrom: $this->usableConnections($network['connections'], $needs),
            usableStops: $this->usableStops($network['stops'], $needs),
            integrations: $network['integrations'],
            needsAccessibleIntegrations: array_any($needs, fn (AccessibilityNeed $need): bool => $need->requirements()['accessible_integration']),
            departure: $this->departure($filters['departure_at'] ?? null),
        );
        $arrivals = collect($search->arrivals($origin, $destination));
        $withoutRides = $arrivals->reject(fn (RouteLabel $arrival): bool => $arrival->hired);
        $fastestMinutes = (int) ($withoutRides->isEmpty() ? $arrivals : $withoutRides)->min('minutes');

        $plans = [];
        foreach (RouteCriterion::cases() as $criterion) {
            /** @var RouteLabel|null $best */
            $best = $arrivals
                ->filter(fn (RouteLabel $arrival): bool => $criterion->accepts($arrival->minutes, $fastestMinutes))
                ->sortBy(fn (RouteLabel $arrival): array => $criterion->sortKey($arrival->measures()))
                ->first();
            if ($best !== null) {
                $plans[$criterion->value] = [
                    'minutes' => $best->minutes,
                    'cost' => $best->cost,
                    'distance' => $best->distance,
                    'transfers' => $best->transfers,
                    'impact' => $best->impact,
                    'steps' => $best->steps(),
                ];
            }
        }

        return $plans;
    }

    /**
     * Get the departure instant in the network time zone: the requested one, or now when none was requested.
     */
    public function departure(?string $departureAt): CarbonImmutable
    {
        return $departureAt === null
            ? CarbonImmutable::now(self::TIMEZONE)
            : CarbonImmutable::parse($departureAt, self::TIMEZONE)->setTimezone(self::TIMEZONE);
    }

    /**
     * Keep the connections that offer what every accessibility need requires of them, grouped by the stop they
     * leave from.
     *
     * @param  EloquentCollection<int, Connection>  $connections
     * @param  list<AccessibilityNeed>  $needs
     * @return Collection<int, EloquentCollection<int, Connection>>
     */
    private function usableConnections(EloquentCollection $connections, array $needs): Collection
    {
        return $connections
            ->filter(fn (Connection $connection): bool => array_all($needs, fn (AccessibilityNeed $need): bool => $need->isMetAlong($connection)))
            ->groupBy('from_stop_id');
    }

    /**
     * Ids of the stops offering what every need requires of the stops the passenger is at, as keys.
     *
     * @param  EloquentCollection<int, Stop>  $stops
     * @param  list<AccessibilityNeed>  $needs
     * @return array<int, true>
     */
    private function usableStops(EloquentCollection $stops, array $needs): array
    {
        return $stops
            ->filter(fn (Stop $stop): bool => array_all($needs, fn (AccessibilityNeed $need): bool => $need->isMetAt($stop)))
            ->map(fn (): bool => true)
            ->all();
    }
}
