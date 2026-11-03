<?php

namespace App\Services;

use App\Enums\TransportModeCode;
use App\Models\TripSearch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TripRepository
{
    public function __construct(private RoutePlanner $planner) {}

    /**
     * Plan the trip, with the accessibility needs of the account if any, and persist the search with one
     * itinerary (and its steps) per criterion found. The search belongs to the account, or for a guest to the
     * session holding the given key. The requested departure is stored in UTC in its own column; "filters" keeps
     * the options the trip was planned with.
     *
     * @param  array{origin_id: int, destination_id: int, modes: array<int, value-of<TransportModeCode>>, departure_at?: ?string}  $input
     */
    public function create(?User $user, string $sessionKey, array $input): TripSearch
    {
        $options = ['modes' => $input['modes'], 'accessibility_needs' => $user->accessibility_needs ?? []];
        $departureAt = isset($input['departure_at']) ? $this->planner->departure($input['departure_at']) : null;

        $plans = $this->planner->plan($input['origin_id'], $input['destination_id'], $options + ['departure_at' => $departureAt?->toIso8601String()]);

        return DB::transaction(function () use ($user, $sessionKey, $input, $departureAt, $options, $plans): TripSearch {
            $search = TripSearch::create([
                'user_id' => $user?->id,
                'session_hash' => TripSearch::hashSessionKey($sessionKey),
                'origin_id' => $input['origin_id'],
                'destination_id' => $input['destination_id'],
                'departure_at' => $departureAt?->utc(),
                'filters' => $options,
            ]);

            foreach ($plans as $criterion => $plan) {
                $itinerary = $search->itineraries()->create([
                    'criterion' => $criterion,
                    'total_minutes' => $plan['minutes'],
                    'total_cost_cents' => $plan['cost'],
                    'total_distance_meters' => $plan['distance'],
                    'transfers' => $plan['transfers'],
                ]);
                $itinerary->steps()->createMany(array_map(
                    fn (array $step, int $index): array => $step + ['sequence' => $index + 1],
                    $plan['steps'],
                    array_keys($plan['steps']),
                ));
            }

            return $search;
        });
    }
}
