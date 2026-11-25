<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Stop;
use Illuminate\Http\JsonResponse;

/**
 * Rede inteira em GeoJSON (FeatureCollection) para desenhar o mapa.
 * Os segmentos são esquemáticos e não representam ruas oficiais.
 */
class NetworkController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $stops = Stop::query()
            ->with('transportMode')
            ->select('stops.*')
            ->selectRaw('ST_AsGeoJSON(location) AS location_geojson')
            ->orderBy('id')
            ->get()
            ->map(fn (Stop $stop) => [
                'type' => 'Feature',
                'geometry' => json_decode($stop->location_geojson, true),
                'properties' => [
                    'kind' => 'stop',
                    'id' => $stop->id,
                    'name' => $stop->name,
                    'mode' => $stop->transportMode->code,
                    'color' => $stop->transportMode->color,
                    'accessible' => $stop->accessible,
                ],
            ]);

        $connections = Connection::query()
            ->withGeoJson()
            ->with(['transportMode', 'line'])
            ->orderBy('id')
            ->get()
            ->map(fn (Connection $connection) => [
                'type' => 'Feature',
                'geometry' => $connection->geometry(),
                'properties' => [
                    'kind' => 'connection',
                    'id' => $connection->id,
                    'from_stop_id' => $connection->from_stop_id,
                    'to_stop_id' => $connection->to_stop_id,
                    'mode' => $connection->transportMode->code,
                    'color' => $connection->transportMode->color,
                    'line' => $connection->line?->code,
                    'minutes' => $connection->minutes,
                ],
            ]);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $stops->concat($connections)->values(),
        ]);
    }
}
