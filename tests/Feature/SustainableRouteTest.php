<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Line;
use App\Models\Stop;
use App\Services\RoutePlanner;
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
        ])['sustainable']['steps'][0]['connection_id'];

        $this->assertSame($bus->id, $sustainable());

        $walk->update(['minutes' => 25]);

        $this->assertSame($walk->id, $sustainable());
    }
}
