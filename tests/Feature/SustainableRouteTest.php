<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Line;
use App\Models\Stop;
use App\Services\Routing\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SustainableRouteTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_sustainable_route_never_takes_more_than_a_quarter_longer_than_the_fastest(): void
    {
        $modes = DB::table('transport_modes')->pluck('id', 'code');
        [$origin, $destination] = Stop::factory()->count(2)->create()->modelKeys();
        $line = Line::factory()->create([
            'transport_mode_id' => $modes['bus'], 'starts_at' => '00:00:00', 'ends_at' => '23:59:00', 'frequency_minutes' => 1,
        ]);
        $bus = Connection::factory()->onLine($line)->create(['from_stop_id' => $origin, 'to_stop_id' => $destination, 'minutes' => 20, 'distance_meters' => 5000]);
        $walk = Connection::factory()->create([
            'from_stop_id' => $origin, 'to_stop_id' => $destination, 'transport_mode_id' => $modes['walk'], 'minutes' => 30, 'distance_meters' => 2000,
        ]);
        $sustainable = fn (): int => app(RoutePlanner::class)->plan($origin, $destination, [
            'modes' => ['bus'], 'departure_at' => '2026-10-30T12:00:00-03:00',
        ])['sustainable']->steps[0]['connection_id'];

        $this->assertSame($bus->id, $sustainable());

        $walk->update(['minutes' => 25]);

        $this->assertSame($walk->id, $sustainable());
    }

    public function test_a_taxi_does_not_set_the_time_limit_of_the_sustainable_route_when_another_way_exists(): void
    {
        [$origin, $destination, $bus, $taxi] = $this->busAndRides();
        $plans = app(RoutePlanner::class)->plan($origin, $destination, ['modes' => ['bus', 'taxi'], 'departure_at' => '2026-10-30T12:00:00-03:00']);

        $this->assertSame($taxi->id, $plans['fastest']->steps[0]['connection_id']);
        $this->assertSame($bus->id, $plans['sustainable']->steps[0]['connection_id']);
    }

    public function test_when_every_route_takes_a_ride_the_fastest_one_sets_the_time_limit(): void
    {
        [$origin, $destination, , $taxi] = $this->busAndRides();
        $plans = app(RoutePlanner::class)->plan($origin, $destination, ['modes' => ['taxi', 'app'], 'departure_at' => '2026-10-30T12:00:00-03:00']);

        $this->assertSame($taxi->id, $plans['sustainable']->steps[0]['connection_id']);
    }

    /**
     * Two stops linked by a bus taking 30 minutes, a taxi ride taking 10 and an app ride taking 13 with the lowest
     * impact of the rides; the bus has the lowest impact of all.
     *
     * @return array{int, int, Connection, Connection, Connection}
     */
    private function busAndRides(): array
    {
        $modes = DB::table('transport_modes')->pluck('id', 'code');
        [$origin, $destination] = Stop::factory()->count(2)->create()->modelKeys();
        $line = Line::factory()->create([
            'transport_mode_id' => $modes['bus'], 'starts_at' => '00:00:00', 'ends_at' => '23:59:00', 'frequency_minutes' => 1,
        ]);
        $ride = fn (string $mode, int $minutes, int $meters): Connection => Connection::factory()->create([
            'from_stop_id' => $origin, 'to_stop_id' => $destination, 'transport_mode_id' => $modes[$mode],
            'minutes' => $minutes, 'distance_meters' => $meters, 'boarding_fare_cents' => 1500,
        ]);

        return [
            $origin,
            $destination,
            Connection::factory()->onLine($line)->create(['from_stop_id' => $origin, 'to_stop_id' => $destination, 'minutes' => 30, 'distance_meters' => 5000]),
            $ride('taxi', 10, 5000),
            $ride('app', 13, 4000),
        ];
    }
}
