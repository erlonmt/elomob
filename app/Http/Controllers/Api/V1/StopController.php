<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogRequest;
use App\Http\Resources\StopResource;
use App\Services\MobilityCatalog;
use App\Services\SpatialNetwork;
use Illuminate\Http\JsonResponse;

class StopController extends Controller
{
    /**
     * List the stops matching the optional text and mode; with coordinates, only those within the radius,
     * closest first.
     */
    public function index(CatalogRequest $request, MobilityCatalog $catalog, SpatialNetwork $spatialNetwork): JsonResponse
    {
        $filters = $request->validated();
        $stops = isset($filters['latitude'], $filters['longitude'])
            ? StopResource::collection($spatialNetwork->nearbyStops($request->float('latitude'), $request->float('longitude'), $request->integer('radius', 1000)))->resolve($request)
            : $catalog->all()['stops'];

        return response()->json(['data' => $catalog->filter($stops, $filters), 'source' => 'demo']);
    }
}
