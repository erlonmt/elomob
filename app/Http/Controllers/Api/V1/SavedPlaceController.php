<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SavePlaceRequest;
use App\Http\Resources\SavedPlaceResource;
use App\Models\SavedPlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavedPlaceController extends Controller
{
    /**
     * List the places saved by the signed-in account, in the order they were saved.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SavedPlaceResource::collection($request->user()->savedPlaces()->with('stop')->orderBy('id')->get());
    }

    /**
     * Save a stop under a label of the signed-in account.
     */
    public function store(SavePlaceRequest $request): JsonResponse
    {
        $savedPlace = $request->user()->savedPlaces()->create($request->validated());

        return (new SavedPlaceResource($savedPlace->load('stop')))->response()->setStatusCode(201);
    }

    /**
     * Delete a saved place. One of another account is answered as not found, so its existence is not revealed.
     */
    public function destroy(Request $request, SavedPlace $savedPlace): JsonResponse
    {
        abort_unless($request->user()->can('delete', $savedPlace), 404);

        $savedPlace->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
