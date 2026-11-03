<?php

namespace App\Services\Routing;

use App\Enums\TransportModeCode;
use App\Models\Connection;
use App\Models\Integration;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use SplPriorityQueue;

/**
 * One multi-criteria search over the part of the network a trip may use. It keeps, for each stop and the service the
 * route arrived with, every partial route no other one beats on all measures (a Pareto front), so a single search
 * finds the best route for every criterion. Routes leave a priority queue by arrival time, and a route some arrival
 * already beats on every measure is dropped, since going further only adds to its measures.
 *
 * Changing service at a stop with an integration registered between the two modes spends its transfer time before
 * boarding, and a fare-integrated change charges only the difference up to the higher fare. A route never passes
 * through a stop twice, so it cannot dodge a transfer by walking away and back.
 */
final readonly class RouteSearch
{
    /**
     * @param  Collection<int, EloquentCollection<int, Connection>>  $connectionsFrom  Usable connections grouped by the stop they leave from, with their mode, line and stops.
     * @param  array<int, true>  $usableStops  Ids of the stops the passenger can be at (every stop but those passed on board must be one).
     * @param  Collection<string, Integration>  $integrations  Integrations keyed by "stop id:mode code arrived with:mode code left with".
     * @param  LineRoutes  $lineRoutes  The order of the stops along each line, which sets when a vehicle leaves each stop.
     * @param  bool  $needsAccessibleIntegrations  Whether changing service must avoid inaccessible integrations.
     * @param  CarbonImmutable  $departure  Departure instant in the network time zone.
     */
    public function __construct(
        private Collection $connectionsFrom,
        private array $usableStops,
        private Collection $integrations,
        private LineRoutes $lineRoutes,
        private bool $needsAccessibleIntegrations,
        private CarbonImmutable $departure,
    ) {}

    /**
     * Find the routes from the origin to the destination, in the order they arrive. No earlier arrival matches or
     * beats one of them on every measure.
     *
     * @return list<RouteLabel>
     */
    public function arrivals(int $origin, int $destination): array
    {
        $start = new RouteLabel(stop: $origin);
        $queued = 0;
        $queue = new SplPriorityQueue;
        $queue->insert($start, $this->priority($start, $queued));
        $fronts = [];
        $arrivals = [];
        while (! $queue->isEmpty()) {
            /** @var RouteLabel $label */
            $label = $queue->extract();
            if (array_any($arrivals, fn (RouteLabel $arrival): bool => $arrival->outranks($label))) {
                continue;
            }
            $key = $label->key();
            $front = $fronts[$key] ?? [];
            if (array_any($front, fn (RouteLabel $kept): bool => $kept->dominates($label))) {
                continue;
            }
            $fronts[$key] = [...array_filter($front, fn (RouteLabel $kept): bool => ! $label->dominates($kept)), $label];
            if ($label->stop === $destination) {
                $arrivals[] = $label;

                continue;
            }
            foreach ($this->connectionsFrom->get($label->stop, []) as $connection) {
                $next = $this->extend($label, $connection, $destination);
                if ($next !== null) {
                    $queue->insert($next, $this->priority($next, ++$queued));
                }
            }
        }

        return $arrivals;
    }

    /**
     * Priority of a route in the queue. SplPriorityQueue extracts the highest priority first, so the values are
     * negated: the earliest arrival leaves first, then the cheapest, the one with fewer transfers and the one queued
     * first.
     *
     * @return array{int, int, int, int}
     */
    private function priority(RouteLabel $label, int $queued): array
    {
        return [-$label->minutes, -$label->cost, -$label->transfers, -$queued];
    }

    /**
     * Extend a route along a connection, or return null when the route cannot take it: its stop was already passed,
     * it is a taxi or app ride right after another one (a single ride from the earlier stop goes straight there),
     * the bike station has no bike or dock, the passenger cannot be at a stop, the integration does not meet their
     * needs or the line no longer runs.
     */
    private function extend(RouteLabel $label, Connection $connection, int $destination): ?RouteLabel
    {
        if ($label->hasVisited($connection->to_stop_id)) {
            return null;
        }
        $mode = TransportModeCode::from($connection->mode->code);
        if ($mode->isHiredPerRide() && $label->mode !== '' && TransportModeCode::from($label->mode)->isHiredPerRide()) {
            return null;
        }
        if ($mode === TransportModeCode::Bike && (($connection->fromStop->bikes_available ?? 0) < 1 || ($connection->toStop->docks_available ?? 0) < 1)) {
            return null;
        }
        $service = $connection->service();
        $passesOnBoard = $service === $label->service && $mode->boardsVehicle();
        if (! $passesOnBoard && ! isset($this->usableStops[$label->stop])) {
            return null;
        }
        if ($connection->to_stop_id === $destination && ! isset($this->usableStops[$destination])) {
            return null;
        }
        $integration = $label->service !== '' && $service !== $label->service
            ? $this->integrations->get($label->stop.':'.$label->mode.':'.$mode->value)
            : null;
        if ($this->needsAccessibleIntegrations && $integration?->accessible === false) {
            return null;
        }
        $transferMinutes = $integration->transfer_minutes ?? 0;
        $boarding = $mode->boardsVehicle() && $service !== $label->service;
        $waitMinutes = $boarding && $connection->line_id
            ? $this->waitingMinutes($connection, $this->departure->addMinutes($label->minutes + $transferMinutes))
            : 0;
        if ($waitMinutes === null) {
            return null;
        }
        $minutes = $connection->minutes + $transferMinutes + $waitMinutes;
        $fare = $boarding ? $connection->fareCents() : 0;
        $fareIntegrated = $boarding && $integration?->fare_integrated === true;
        $cost = $fareIntegrated ? max(0, $fare - $label->paidFare) : $fare;

        return new RouteLabel(
            stop: $connection->to_stop_id,
            service: $service,
            mode: $mode->value,
            boarded: $label->boarded || $boarding,
            hired: $label->hired || $mode->isHiredPerRide(),
            publicTransportOnly: $label->publicTransportOnly && ($mode->isPublicTransport() || ! $mode->boardsVehicle()),
            minutes: $label->minutes + $minutes,
            cost: $label->cost + $cost,
            paidFare: match (true) {
                $fareIntegrated => max($label->paidFare, $fare),
                $boarding => $fare,
                default => $label->paidFare,
            },
            distance: $label->distance + $connection->distance_meters,
            transfers: $label->transfers + ($boarding && $label->boarded ? 1 : 0),
            impact: $label->impact + $connection->distance_meters * $mode->impactPerMeter(),
            step: [
                'connection_id' => $connection->id,
                'minutes' => $minutes,
                'transfer_minutes' => $transferMinutes,
                'wait_minutes' => $waitMinutes,
                'cost_cents' => $cost,
                'fare_integrated' => $fareIntegrated,
                'distance_meters' => $connection->distance_meters,
            ],
            previous: $label,
        );
    }

    /**
     * Minutes until the next vehicle of a frequency-based line leaves the connection's stop, or null when the line no
     * longer runs that day. Vehicles leave the first stop of their way at the line's times and each later stop as
     * many minutes later as the stretches before it take, so the arrival is moved back by those minutes.
     */
    private function waitingMinutes(Connection $connection, CarbonImmutable $arrivalAtStop): ?int
    {
        $line = $connection->line;
        $arrival = $arrivalAtStop->subMinutes($this->lineRoutes->departureOffset($line->id, $connection->from_stop_id, $connection->to_stop_id));
        $start = $arrival->startOfDay()->setTimeFromTimeString($line->starts_at);
        $end = $arrival->startOfDay()->setTimeFromTimeString($line->ends_at);
        if ($end->lessThan($start)) {
            if ($arrival->lessThanOrEqualTo($end)) {
                $start = $start->subDay();
            } else {
                $end = $end->addDay();
            }
        }
        if ($arrival->greaterThan($end) || $line->frequency_minutes < 1) {
            return null;
        }
        $frequencySeconds = $line->frequency_minutes * 60;
        $elapsedSeconds = max(0, (int) $start->diffInSeconds($arrival));
        $serviceDeparture = $start->addSeconds((int) ceil($elapsedSeconds / $frequencySeconds) * $frequencySeconds);
        if ($serviceDeparture->greaterThan($end)) {
            return null;
        }

        return (int) ceil($arrival->diffInSeconds($serviceDeparture) / 60);
    }
}
