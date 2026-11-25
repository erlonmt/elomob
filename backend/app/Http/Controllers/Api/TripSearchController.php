<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanTripRequest;
use App\Http\Resources\TripSearchResource;
use App\Models\TripSearch;
use App\Services\Planning\TripPlanningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Buscas de trajeto. Funciona sem conta: o visitante recebe um `guest_token`
 * e o envia no cabeçalho X-Guest-Token para reabrir a busca.
 */
class TripSearchController extends Controller
{
    public const GUEST_TOKEN_HEADER = 'X-Guest-Token';

    /** Histórico de buscas da conta autenticada. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $searches = $request->user()->tripSearches()
            ->with(['origin', 'destination'])
            ->latest()
            ->paginate(15);

        return TripSearchResource::collection($searches);
    }

    public function store(PlanTripRequest $request, TripPlanningService $planning): JsonResponse
    {
        $result = $planning->plan($request->validated(), $request->user('sanctum'));

        $resource = new TripSearchResource($this->loadResults($result['search']));

        if ($result['guest_token'] !== null) {
            $resource->additional(['guest_token' => $result['guest_token']]);
        }

        return $resource->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, TripSearch $tripSearch): TripSearchResource
    {
        abort_unless($this->canView($request, $tripSearch), 404);

        return new TripSearchResource($this->loadResults($tripSearch));
    }

    /** Respondemos 404, e não 403, para não revelar que a busca existe. */
    private function canView(Request $request, TripSearch $search): bool
    {
        if ($search->user_id !== null) {
            return $request->user('sanctum')?->id === $search->user_id;
        }

        $token = $request->header(self::GUEST_TOKEN_HEADER);

        return is_string($token) && hash_equals($search->session_hash, TripSearch::hashToken($token));
    }

    private function loadResults(TripSearch $search): TripSearch
    {
        return $search->load([
            'origin.transportMode',
            'destination.transportMode',
            'itineraries' => fn ($q) => $q->orderBy('id'),
            'itineraries.steps.connection' => fn ($q) => $q->withGeoJson(),
            'itineraries.steps.connection.transportMode',
            'itineraries.steps.connection.line',
            'itineraries.steps.connection.fromStop',
            'itineraries.steps.connection.toStop',
        ]);
    }
}
