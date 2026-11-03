<?php

namespace Tests\Feature;

use App\Enums\TransportModeCode;
use App\Models\Connection;
use App\Models\Stop;
use App\Services\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobilityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-30 14:30:00', 'America/Recife'));
    }

    private function stop(string $name): int
    {
        return (int) DB::table('stops')->where('name', $name)->value('id');
    }

    /** @return array{origin_id: int, destination_id: int, modes: array<int, string>} */
    private function searchData(): array
    {
        return ['origin_id' => $this->stop('Boa Viagem'), 'destination_id' => $this->stop('Recife Antigo'), 'modes' => ['bus', 'metro', 'bike', 'taxi', 'app']];
    }

    public function test_search_persists_consistent_routes_and_steps(): void
    {
        $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated();
        $plans = DB::table('itineraries')->get();
        $this->assertCount(4, $plans);
        foreach ($plans as $plan) {
            $steps = DB::table('itinerary_steps')->where('itinerary_id', $plan->id);
            $this->assertEquals($plan->total_minutes, (clone $steps)->sum('minutes'));
            $this->assertEquals($plan->total_cost_cents, (clone $steps)->sum('cost_cents'));
            $this->assertEquals($plan->total_distance_meters, (clone $steps)->sum('distance_meters'));
        }
        $this->assertEquals(25, $plans->firstWhere('criterion', 'fastest')->total_minutes);
        $this->assertEquals(380, $plans->firstWhere('criterion', 'cheapest')->total_cost_cents);
    }

    public function test_continuing_on_the_same_bus_does_not_charge_three_fares(): void
    {
        $plans = app(RoutePlanner::class)->plan($this->stop('Cordeiro'), $this->stop('Recife Antigo'), ['modes' => ['bus']]);
        $this->assertEquals(380, $plans['cheapest']['cost']);
        $this->assertEquals(35, $plans['cheapest']['minutes']);
        $this->assertEquals(0, $plans['cheapest']['transfers']);
    }

    public function test_the_fare_charged_on_a_line_is_the_fare_of_the_line(): void
    {
        DB::table('lines')->whereIn('code', ['081', 'BRT'])->update(['fare_cents' => 450]);

        $plans = app(RoutePlanner::class)->plan($this->stop('Cordeiro'), $this->stop('Recife Antigo'), ['modes' => ['bus']]);

        $this->assertSame(450, $plans['cheapest']['cost']);
        $this->assertSame([450, 0, 0], array_column($plans['cheapest']['steps'], 'cost_cents'));
    }

    public function test_filters_and_accessibility_change_actual_calculation(): void
    {
        $fastest = fn (array $filters): int => app(RoutePlanner::class)->plan($this->stop('Boa Viagem'), $this->stop('Recife Antigo'), $filters)['fastest']['minutes'];

        $this->assertSame(47, $fastest(['modes' => ['bus', 'metro']]));
        $this->assertSame(52, $fastest(['modes' => ['bus']]));
        $this->assertSame(47, $fastest(['modes' => ['bus', 'metro'], 'accessibility_needs' => ['reduced_mobility']]));
        $this->postJson('/api/v1/buscas', $this->searchData() + ['unexpected' => 'ignored'])->assertCreated();
    }

    public function test_seeding_is_repeatable(): void
    {
        $this->seed();
        $this->assertDatabaseCount('transport_modes', 6);
        $this->assertDatabaseCount('stops', 17);
        $this->assertDatabaseCount('connections', 580);
    }

    public function test_taxi_and_app_reach_any_two_stops_at_least_a_kilometer_apart_priced_by_distance(): void
    {
        $ride = fn (string $from, string $to, string $mode): ?object => DB::table('connections')
            ->where('from_stop_id', $this->stop($from))->where('to_stop_id', $this->stop($to))
            ->where('transport_mode_id', DB::table('transport_modes')->where('code', $mode)->value('id'))
            ->first(['minutes', 'distance_meters', 'boarding_fare_cents']);
        $values = fn (?object $ride): array => [$ride?->minutes, $ride?->distance_meters, $ride?->boarding_fare_cents];

        $this->assertSame([32, 9758, 2206], $values($ride('Várzea', 'Pina', 'taxi')));
        $this->assertSame([36, 9758, 1912], $values($ride('Várzea', 'Pina', 'app')));
        $this->assertSame([25, 7800, 1850], $values($ride('Boa Viagem', 'Recife Antigo', 'taxi')));
        $this->assertSame([29, 7800, 1600], $values($ride('Boa Viagem', 'Recife Antigo', 'app')));
        $this->assertNull($ride('Boa Viagem', 'Bike Recife — Est. 12', 'taxi'));
        foreach (['taxi', 'app'] as $mode) {
            $this->assertSame(266, DB::table('connections')->where('transport_mode_id', DB::table('transport_modes')->where('code', $mode)->value('id'))->count(), $mode);
        }
    }

    public function test_a_hired_ride_never_follows_another_one(): void
    {
        $modes = DB::table('transport_modes')->pluck('id', 'code');
        [$first, $middle, $last] = Stop::factory()->count(3)->create()->modelKeys();
        foreach ([[$first, $middle, 'taxi'], [$middle, $last, 'app']] as [$from, $to, $mode]) {
            Connection::factory()->create([
                'from_stop_id' => $from, 'to_stop_id' => $to, 'transport_mode_id' => $modes[$mode], 'minutes' => 10, 'distance_meters' => 3000, 'boarding_fare_cents' => 1000,
            ]);
        }
        $plans = fn (): array => app(RoutePlanner::class)->plan($first, $last, ['modes' => ['taxi', 'app'], 'departure_at' => '2026-10-30T12:00:00-03:00']);

        $this->assertSame([], $plans());

        Connection::factory()->create([
            'from_stop_id' => $first, 'to_stop_id' => $last, 'transport_mode_id' => $modes['taxi'], 'minutes' => 18, 'distance_meters' => 5500, 'boarding_fare_cents' => 1600,
        ]);

        $this->assertSame([18, 1600], [$plans()['fastest']['minutes'], $plans()['fastest']['cost']]);
    }

    public function test_late_at_night_taxi_and_app_still_reach_the_destination(): void
    {
        $plans = app(RoutePlanner::class)->plan($this->stop('Várzea'), $this->stop('Pina'), [
            'modes' => ['bus', 'metro', 'taxi', 'app'], 'departure_at' => '2026-10-30T23:30:00-03:00',
        ]);

        $this->assertSame([32, 2206], [$plans['fastest']['minutes'], $plans['fastest']['cost']]);
        $this->assertSame([36, 1912], [$plans['cheapest']['minutes'], $plans['cheapest']['cost']]);
    }

    public function test_seeded_transport_mode_codes_match_the_enum_in_id_order(): void
    {
        $this->assertSame(
            array_column(TransportModeCode::cases(), 'value'),
            DB::table('transport_modes')->orderBy('id')->pluck('code')->all(),
        );
    }
}
