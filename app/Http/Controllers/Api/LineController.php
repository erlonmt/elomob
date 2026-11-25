<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LineResource;
use App\Models\Line;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LineController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $lines = Line::query()
            ->with('transportMode')
            ->when($request->filled('mode'), fn ($q) => $q->whereHas(
                'transportMode', fn ($q) => $q->where('code', $request->input('mode'))
            ))
            ->orderBy('code')
            ->get();

        return LineResource::collection($lines);
    }

    public function show(Line $line): LineResource
    {
        return new LineResource($line->load([
            'transportMode',
            'connections' => fn ($q) => $q->orderBy('id'),
            'connections.fromStop',
            'connections.toStop',
        ]));
    }
}
