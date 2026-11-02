<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogRequest;
use App\Services\MobilityCatalog;
use Illuminate\Http\JsonResponse;

class LineController extends Controller
{
    /**
     * List the lines matching the optional text and mode.
     */
    public function index(CatalogRequest $request, MobilityCatalog $catalog): JsonResponse
    {
        return response()->json(['data' => $catalog->filter($catalog->all()['lines'], $request->validated()), 'source' => 'demo']);
    }
}
