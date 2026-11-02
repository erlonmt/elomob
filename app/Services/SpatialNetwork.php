<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Itinerary;
use App\Models\ItineraryStep;
use App\Models\Stop;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SpatialNetwork
{
    /**
     * Get the stops within the radius, closest first, each with its "distance_meters" and "mode" loaded.
     * PostgreSQL uses the PostGIS geography index; other drivers fall back to the haversine formula.
     *
     * @return Collection<int, Stop>
     */
    public function nearbyStops(float $latitude, float $longitude, int $radiusMeters = 25000): Collection
    {
        $query = Stop::query()->with('mode');

        if (DB::getDriverName() === 'pgsql') {
            return $query->select('stops.*')
                ->selectRaw('ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) AS distance_meters', [$longitude, $latitude])
                ->whereRaw('ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)', [$longitude, $latitude, $radiusMeters])
                ->orderBy('distance_meters')->orderBy('id')->get();
        }

        return $query->get()->each(function (Stop $stop) use ($latitude, $longitude): void {
            $latitudeDifference = deg2rad($stop->latitude - $latitude);
            $longitudeDifference = deg2rad($stop->longitude - $longitude);
            $haversine = sin($latitudeDifference / 2) ** 2 + cos(deg2rad($latitude)) * cos(deg2rad($stop->latitude)) * sin($longitudeDifference / 2) ** 2;
            $stop->distance_meters = 6371008.8 * 2 * asin(sqrt(min(1, $haversine)));
        })->filter(fn (Stop $stop): bool => $stop->distance_meters <= $radiusMeters)
            ->sortBy([['distance_meters', 'asc'], ['id', 'asc']])->values();
    }

    /**
     * Describe the itinerary as a GeoJSON FeatureCollection with one LineString per step, in order.
     * Steps already loaded (e.g. by ItineraryController) are reused; only the PostGIS paths are queried.
     *
     * @return array{type: string, features: list<array<string, mixed>>}
     */
    public function itineraryGeoJson(Itinerary $itinerary): array
    {
        $itinerary->loadMissing(array_map(fn (string $relation): string => 'steps.'.$relation, ItineraryStep::DETAILS));
        $paths = Connection::query()->withPathGeoJson()->whereKey($itinerary->steps->pluck('connection_id')->unique())
            ->get()->pluck('path_geojson', 'id');

        $features = $itinerary->steps->map(function (ItineraryStep $step) use ($paths): array {
            /** @var Connection $connection */
            $connection = $step->connection;

            return ['type' => 'Feature', 'id' => $step->id, 'geometry' => $connection->geometry($paths->get($step->connection_id)), 'properties' => [
                'sequence' => $step->sequence,
                'connection_id' => $step->connection_id,
                'mode' => $connection->mode->code,
                'mode_name' => $connection->mode->name,
                'color' => $connection->mode->color,
                'origin' => $connection->fromStop->name,
                'destination' => $connection->toStop->name,
                'line_code' => $connection->line?->code,
                'minutes' => $step->minutes,
                'transfer_minutes' => $step->transfer_minutes,
                'wait_minutes' => $step->wait_minutes,
                'distance_meters' => $step->distance_meters,
            ]];
        })->all();

        return ['type' => 'FeatureCollection', 'features' => $features];
    }
}
