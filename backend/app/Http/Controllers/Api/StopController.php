<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NearbyStopsRequest;
use App\Http\Resources\LineResource;
use App\Http\Resources\StopResource;
use App\Models\Line;
use App\Models\Stop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StopController extends Controller
{
    /**
     * Lista paradas. Com `lat` e `lng`, devolve as próximas ordenadas pela
     * distância (PostGIS); com `q`, filtra pelo nome; com `mode`, pelo modal.
     */
    public function index(NearbyStopsRequest $request): AnonymousResourceCollection
    {
        $query = Stop::query()->with('transportMode');

        if ($request->filled('lat')) {
            $query->near(
                (float) $request->input('lat'),
                (float) $request->input('lng'),
                (int) $request->input('radius', config('elomob.nearby_radius_meters')),
            );
        } else {
            $query->orderBy('name');
        }

        if ($request->filled('q')) {
            $query->where('name', 'ILIKE', '%'.$request->input('q').'%');
        }

        if ($request->filled('mode')) {
            $query->whereHas('transportMode', fn ($q) => $q->where('code', $request->input('mode')));
        }

        return StopResource::collection($query->get());
    }

    public function show(Stop $stop): JsonResponse
    {
        $stop->load('transportMode');

        $lines = Line::query()
            ->with('transportMode')
            ->whereHas('connections', fn ($q) => $q->where(
                fn ($q) => $q->where('from_stop_id', $stop->id)->orWhere('to_stop_id', $stop->id)
            ))
            ->orderBy('code')
            ->get();

        return response()->json([
            'data' => [
                ...(new StopResource($stop))->resolve(),
                'lines' => LineResource::collection($lines)->resolve(),
            ],
        ]);
    }
}
