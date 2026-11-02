<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItineraryDetailResource;
use App\Models\Itinerary;
use App\Models\ItineraryStep;
use App\Models\TripSearch;
use App\Services\SpatialNetwork;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ItineraryController extends Controller
{
    /**
     * Show an itinerary with its steps and its map geometry. An itinerary of another account's or session's
     * search is answered as not found, so its existence is not revealed.
     */
    public function show(Request $request, Itinerary $itinerary, SpatialNetwork $spatialNetwork): ItineraryDetailResource
    {
        abort_unless(Gate::allows('view', [$itinerary->tripSearch, $request->session()->get(TripSearch::SESSION_KEY)]), 404);

        $itinerary->loadMissing([
            'tripSearch.origin',
            'tripSearch.destination',
            'steps' => fn (HasMany $query) => $query->with(ItineraryStep::DETAILS),
        ]);

        return (new ItineraryDetailResource($itinerary, $spatialNetwork->itineraryGeoJson($itinerary)))->additional(['source' => 'demo']);
    }
}
