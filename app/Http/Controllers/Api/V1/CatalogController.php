<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MobilityCatalog;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    /**
     * Show the whole public catalog: stops, selectable modes, lines and integrations.
     */
    public function __invoke(MobilityCatalog $catalog): JsonResponse
    {
        return response()->json(['data' => $catalog->all(), 'source' => 'demo']);
    }
}
