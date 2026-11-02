<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRouteRequest;
use App\Http\Resources\TripSearchResource;
use App\Models\ItineraryStep;
use App\Models\TripSearch;
use App\Services\TripRepository;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TripSearchController extends Controller
{
    /**
     * List the latest searches of the signed-in account, newest first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $searches = $request->user()->tripSearches()->with(['origin', 'destination'])->latest('id')->limit(20)->get();

        return TripSearchResource::collection($searches);
    }

    /**
     * Delete every search of the signed-in account with its itineraries and steps. Favorites stay.
     */
    public function clear(Request $request): JsonResponse
    {
        $deleted = $request->user()->tripSearches()->delete();

        return response()->json(['data' => ['deleted' => $deleted]]);
    }

    /**
     * Plan and save a search, tied to the signed-in account or, for a guest, to the browser session.
     */
    public function store(SearchRouteRequest $request, TripRepository $trips): JsonResponse
    {
        $sessionKey = $request->session()->remember(TripSearch::SESSION_KEY, fn (): string => (string) Str::uuid());
        $search = $trips->create($request->user(), $sessionKey, $request->validated());

        return $this->results($search)->response()->setStatusCode(201);
    }

    /**
     * Show a search with its itineraries. A search of another account or session is answered as not found,
     * so its existence is not revealed.
     */
    public function show(Request $request, TripSearch $tripSearch): TripSearchResource
    {
        abort_unless(Gate::allows('view', [$tripSearch, $request->session()->get(TripSearch::SESSION_KEY)]), 404);

        return $this->results($tripSearch);
    }

    /**
     * Describe the search with its itineraries and their steps.
     */
    private function results(TripSearch $search): TripSearchResource
    {
        $search->load([
            'origin',
            'destination',
            'itineraries.steps' => fn (HasMany $query) => $query->with(ItineraryStep::DETAILS),
        ]);

        return (new TripSearchResource($search))->additional(['source' => 'demo']);
    }
}
