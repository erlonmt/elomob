<?php

namespace App\Services\Planning;

use App\Enums\AccessibilityNeed;
use App\Enums\RouteCriterion;
use App\Exceptions\NoRouteFoundException;
use App\Models\TripSearch;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Resolve as opções de uma busca, calcula um itinerário por critério e salva
 * a busca com seus resultados em uma transação.
 */
class TripPlanningService
{
    public function __construct(
        private readonly NetworkRepository $networks,
        private readonly RoutePlanner $planner,
    ) {}

    /**
     * @param  array{origin_id: int, destination_id: int, departure_at?: string|null, modes?: list<string>|null, accessibility_needs?: list<string>|null}  $input
     * @return array{search: TripSearch, guest_token: string|null}
     */
    public function plan(array $input, ?User $user): array
    {
        $network = $this->networks->current();

        $modes = $input['modes'] ?? $user?->transport_modes ?? [];
        $needs = $input['accessibility_needs'] ?? $user?->accessibility_needs ?? [];
        $departureUtc = isset($input['departure_at']) ? CarbonImmutable::parse($input['departure_at'])->utc() : null;

        $options = new PlanningOptions(
            departure: ($departureUtc ?? CarbonImmutable::now())->setTimezone(config('elomob.timezone')),
            allowedModeIds: array_values(array_filter(array_map($network->modeId(...), $modes))),
            needs: array_map(AccessibilityNeed::from(...), $needs),
        );

        $routes = [];
        foreach (RouteCriterion::cases() as $criterion) {
            $route = $this->planner->plan($network, (int) $input['origin_id'], (int) $input['destination_id'], $criterion, $options);

            if ($route !== null) {
                $routes[] = $route;
            }
        }

        if ($routes === []) {
            throw new NoRouteFoundException;
        }

        // Visitantes recebem um token para reabrir a busca; só o hash fica no banco.
        $token = Str::random(40);

        $search = DB::transaction(function () use ($input, $user, $modes, $needs, $departureUtc, $routes, $token) {
            $search = TripSearch::create([
                'user_id' => $user?->id,
                'session_hash' => TripSearch::hashToken($token),
                'origin_id' => $input['origin_id'],
                'destination_id' => $input['destination_id'],
                'departure_at' => $departureUtc,
                'filters' => ['modes' => array_values($modes), 'accessibility_needs' => array_values($needs)],
            ]);

            foreach ($routes as $route) {
                $itinerary = $search->itineraries()->create($route->itineraryAttributes());
                $itinerary->steps()->createMany($route->steps);
            }

            return $search;
        });

        return ['search' => $search, 'guest_token' => $user === null ? $token : null];
    }
}
