<?php

namespace Tests\Feature;

use App\Models\Itinerary;
use App\Services\Routing\RoutePlanner;
use App\Services\SpatialNetwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SpatialNetworkTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_nearby_stops_are_filtered_by_meters_and_ordered_by_distance(): void
    {
        $network = app(SpatialNetwork::class);
        $nearby = $network->nearbyStops(-8.119, -34.899, 500);

        $this->assertSame(['Boa Viagem', 'Bike Recife — Est. 12'], $nearby->pluck('name')->all());
        $this->assertEqualsWithDelta(0, $nearby->first()->distance_meters, 0.01);
        $this->assertGreaterThan(300, $nearby->last()->distance_meters);
        $this->assertCount(1, $network->nearbyStops(-8.119, -34.899, 100));
        $this->assertCount(0, $network->nearbyStops(0, 0, 500));
    }

    public function test_geojson_contains_the_actual_ordered_connections_and_waiting_time(): void
    {
        $origin = (int) DB::table('stops')->where('name', 'Cordeiro')->value('id');
        $destination = (int) DB::table('stops')->where('name', 'Recife Antigo')->value('id');
        $plan = app(RoutePlanner::class)->plan($origin, $destination, ['modes' => ['bus'], 'departure_at' => '2026-10-30T14:31:00-03:00'])['fastest'];
        $searchId = DB::table('trip_searches')->insertGetId(['session_hash' => str_repeat('a', 64), 'origin_id' => $origin, 'destination_id' => $destination, 'filters' => '{}', 'created_at' => now(), 'updated_at' => now()]);
        $itineraryId = DB::table('itineraries')->insertGetId(['trip_search_id' => $searchId, 'criterion' => 'fastest', 'total_minutes' => $plan->minutes, 'total_cost_cents' => $plan->cost, 'total_distance_meters' => $plan->distance, 'transfers' => $plan->transfers]);
        foreach ($plan->steps as $index => $step) {
            DB::table('itinerary_steps')->insert($step + ['itinerary_id' => $itineraryId, 'sequence' => $index + 1]);
        }

        $geometry = app(SpatialNetwork::class)->itineraryGeoJson(Itinerary::findOrFail($itineraryId));

        $this->assertSame('FeatureCollection', $geometry['type']);
        $this->assertCount(3, $geometry['features']);
        $this->assertSame([1, 2, 3], array_column(array_column($geometry['features'], 'properties'), 'sequence'));
        $this->assertSame('LineString', $geometry['features'][0]['geometry']['type']);
        $this->assertEquals([-34.932, -8.043], $geometry['features'][0]['geometry']['coordinates'][0]);
        $this->assertEquals([-34.871, -8.063], $geometry['features'][2]['geometry']['coordinates'][1]);
        $this->assertSame(2, $geometry['features'][0]['properties']['wait_minutes']);
        $this->assertSame('081', $geometry['features'][0]['properties']['line_code']);
    }

    public function test_postgis_coordinates_and_connection_geometry_stay_synchronized(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostGIS integration requires PostgreSQL.');
        }

        $this->assertNotNull(DB::table('pg_extension')->where('extname', 'postgis')->first());
        $this->assertSame(2, DB::table('pg_indexes')->whereIn('indexname', ['stops_location_gist', 'connections_path_gist'])->count());
        $this->assertSame(0, DB::table('connections')->whereNull('path')->count());
        DB::table('stops')->where('name', 'Boa Viagem')->update(['latitude' => -8.120, 'longitude' => -34.900]);
        $stop = DB::table('stops')->where('name', 'Boa Viagem')->selectRaw('id, ST_X(location::geometry) AS longitude, ST_Y(location::geometry) AS latitude, ST_SRID(location::geometry) AS srid')->first();
        $connection = DB::table('connections')->where('from_stop_id', $stop->id)->selectRaw('ST_X(ST_StartPoint(path)) AS longitude, ST_Y(ST_StartPoint(path)) AS latitude')->first();

        $this->assertEquals(-34.900, $stop->longitude);
        $this->assertEquals(-8.120, $stop->latitude);
        $this->assertSame(4326, $stop->srid);
        $this->assertEquals($stop->longitude, $connection->longitude);
        $this->assertEquals($stop->latitude, $connection->latitude);
    }
}
