<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavedPlaceRequest;
use App\Http\Resources\SavedPlaceResource;
use App\Models\SavedPlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SavedPlaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SavedPlaceResource::collection(
            $request->user()->savedPlaces()->with('stop.transportMode')->orderBy('label')->get()
        );
    }

    public function store(StoreSavedPlaceRequest $request): JsonResponse
    {
        $place = $request->user()->savedPlaces()->create($request->validated());

        return (new SavedPlaceResource($place->load('stop.transportMode')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, SavedPlace $savedPlace): Response
    {
        abort_unless($savedPlace->user_id === $request->user()->id, 404);

        $savedPlace->delete();

        return response()->noContent();
    }
}
