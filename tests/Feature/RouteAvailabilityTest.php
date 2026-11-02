<?php

namespace Tests\Feature;

use App\Services\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RouteAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_bicycle_routes_require_bikes_at_origin_and_free_docks_at_destination(): void
    {
        $planner = app(RoutePlanner::class);
        $origin = $this->stop('Boa Viagem');
        $destination = $this->stop('Recife Antigo');
        $filters = ['modes' => ['bike'], 'departure_at' => '2026-10-30T14:30:00-03:00'];
        $this->assertCount(4, $planner->plan($origin, $destination, $filters));

        DB::table('stops')->where('name', 'Bike Recife — Est. 12')->update(['bikes_available' => 0]);
        $this->assertSame([], $planner->plan($origin, $destination, $filters));

        DB::table('stops')->where('name', 'Bike Recife — Est. 12')->update(['bikes_available' => 4]);
        DB::table('stops')->where('id', $destination)->update(['docks_available' => 0]);
        $this->assertSame([], $planner->plan($origin, $destination, $filters));
    }

    public function test_lines_after_the_last_departure_are_excluded(): void
    {
        $this->assertSame([], $this->busPlans('2026-10-30T23:00:00-03:00'));
    }

    public function test_departure_before_opening_counts_waiting_until_the_first_service(): void
    {
        $plan = $this->busPlans('2026-10-30T04:00:00-03:00')['fastest'];

        $this->assertSame(122, $plan['minutes']);
        $this->assertSame([90, 0, 0], array_column($plan['steps'], 'wait_minutes'));
    }

    public function test_frequency_wait_is_counted_once_when_continuing_on_the_same_line(): void
    {
        $plan = $this->busPlans('2026-10-30T14:31:00-03:00')['fastest'];

        $this->assertSame(46, $plan['minutes']);
        $this->assertSame(380, $plan['cost']);
        $this->assertSame(0, $plan['transfers']);
        $this->assertSame([14, 0, 0], array_column($plan['steps'], 'wait_minutes'));
        $this->assertSame($plan['minutes'], array_sum(array_column($plan['steps'], 'minutes')));
        $this->assertEquals($plan, $this->busPlans('2026-10-30T17:31:00Z')['fastest']);
    }

    public function test_a_transfer_is_unavailable_when_its_line_closes_before_arrival(): void
    {
        DB::table('lines')->whereIn('code', ['CENTRO', 'SUL'])->update(['ends_at' => '14:40:00']);
        DB::table('lines')->whereIn('code', ['033', '061'])->update(['ends_at' => '14:00:00']);
        $plans = app(RoutePlanner::class)->plan($this->stop('Boa Viagem'), $this->stop('Recife Antigo'), [
            'modes' => ['bus', 'metro'], 'departure_at' => '2026-10-30T14:30:00-03:00',
        ]);
        $this->assertSame([], $plans);
    }

    public function test_overnight_lines_use_the_previous_days_service_window_after_midnight(): void
    {
        DB::table('lines')->where('code', '081')->update(['starts_at' => '22:00:00', 'ends_at' => '02:00:00']);
        $this->withoutLines('BRT');
        $plan = $this->busPlans('2026-10-31T01:31:00-03:00')['fastest'];

        $this->assertSame(46, $plan['minutes']);
        $this->assertSame(1172, $this->busPlans('2026-10-31T03:00:00-03:00')['fastest']['minutes']);
    }

    public function test_an_earlier_costlier_arrival_is_preserved_to_catch_the_last_service(): void
    {
        $modes = DB::table('transport_modes')->pluck('id', 'code');
        $line = (int) DB::table('lines')->where('code', 'CENTRO')->value('id');
        DB::table('lines')->whereIn('code', ['033', '081', '061'])->update(['ends_at' => '14:00:00']);
        DB::table('lines')->where('id', $line)->update(['starts_at' => '14:00:00', 'ends_at' => '14:50:00', 'frequency_minutes' => 1]);
        DB::table('connections')->where('transport_mode_id', $modes['metro'])->delete();
        $taxi = DB::table('connections')->insertGetId([
            'from_stop_id' => $this->stop('Boa Viagem'), 'to_stop_id' => $this->stop('Estação Joana Bezerra'),
            'transport_mode_id' => $modes['taxi'], 'minutes' => 5, 'distance_meters' => 4200, 'boarding_fare_cents' => 1000,
        ]);
        DB::table('connections')->insert([
            'from_stop_id' => $this->stop('Afogados'), 'to_stop_id' => $this->stop('Estação Recife'),
            'transport_mode_id' => $modes['metro'], 'line_id' => $line, 'minutes' => 10, 'distance_meters' => 1800,
        ]);

        $plan = app(RoutePlanner::class)->plan($this->stop('Boa Viagem'), $this->stop('Recife Antigo'), [
            'modes' => ['bus', 'metro', 'taxi'], 'departure_at' => '2026-10-30T14:30:00-03:00',
        ])['cheapest'];

        $this->assertSame(1160, $plan['cost']);
        $this->assertSame(34, $plan['minutes']);
        $this->assertEquals($taxi, $plan['steps'][0]['connection_id']);
    }

    public function test_an_earlier_cheaper_arrival_can_wait_for_a_line_to_open(): void
    {
        $modes = DB::table('transport_modes')->pluck('id', 'code');
        $line = (int) DB::table('lines')->where('code', 'CENTRO')->value('id');
        DB::table('lines')->whereIn('code', ['033', '081'])->update(['ends_at' => '14:00:00']);
        DB::table('lines')->where('code', '042')->update(['starts_at' => '14:00:00', 'frequency_minutes' => 1, 'fare_cents' => 100]);
        DB::table('lines')->where('id', $line)->update(['starts_at' => '14:50:00', 'ends_at' => '15:50:00', 'frequency_minutes' => 1]);
        DB::table('connections')->where('line_id', DB::table('lines')->where('code', '042')->value('id'))->update(['minutes' => 5]);
        DB::table('connections')->where('transport_mode_id', $modes['metro'])->delete();
        DB::table('connections')->insert([
            'from_stop_id' => $this->stop('Boa Viagem'), 'to_stop_id' => $this->stop('Estação Joana Bezerra'),
            'transport_mode_id' => $modes['taxi'], 'minutes' => 10, 'distance_meters' => 4200, 'boarding_fare_cents' => 200,
        ]);
        DB::table('connections')->insert([
            'from_stop_id' => $this->stop('Afogados'), 'to_stop_id' => $this->stop('Estação Recife'),
            'transport_mode_id' => $modes['metro'], 'line_id' => $line, 'minutes' => 10, 'distance_meters' => 1800,
        ]);

        $plan = app(RoutePlanner::class)->plan($this->stop('Boa Viagem'), $this->stop('Recife Antigo'), [
            'modes' => ['bus', 'metro', 'taxi'], 'departure_at' => '2026-10-30T14:30:00-03:00',
        ])['cheapest'];

        $this->assertSame(260, $plan['cost']);
        $this->assertSame(37, $plan['minutes']);
        $this->assertSame([0, 0, 3, 0], array_column($plan['steps'], 'wait_minutes'));
    }

    public function test_changing_modes_at_an_integration_spends_its_transfer_time_before_boarding(): void
    {
        $this->withoutLines('061');
        $plan = $this->busAndMetroPlans()['fastest'];

        $this->assertSame([0, 3, 0], array_column($plan['steps'], 'transfer_minutes'));
        $this->assertSame([6, 3, 0], array_column($plan['steps'], 'wait_minutes'));
        $this->assertSame([24, 16, 7], array_column($plan['steps'], 'minutes'));
        $this->assertSame(47, $plan['minutes']);

        DB::table('integrations')->where('stop_id', $this->stop('Estação Joana Bezerra'))->update(['transfer_minutes' => 8]);
        $plan = $this->busAndMetroPlans()['fastest'];

        $this->assertSame([0, 8, 0], array_column($plan['steps'], 'transfer_minutes'));
        $this->assertSame([6, 8, 0], array_column($plan['steps'], 'wait_minutes'));
        $this->assertSame(57, $plan['minutes']);
    }

    public function test_with_two_lines_on_the_same_stretch_each_criterion_picks_its_own(): void
    {
        $this->withoutLines('061');
        $bus = (int) DB::table('transport_modes')->where('code', 'bus')->value('id');
        $express = DB::table('lines')->insertGetId([
            'transport_mode_id' => $bus, 'code' => '042E', 'name' => 'Boa Viagem → Centro (expresso)',
            'starts_at' => '14:31:00', 'ends_at' => '23:00:00', 'frequency_minutes' => 30, 'fare_cents' => 450,
        ]);
        DB::table('connections')->insert([
            'from_stop_id' => $this->stop('Boa Viagem'), 'to_stop_id' => $this->stop('Estação Joana Bezerra'),
            'transport_mode_id' => $bus, 'line_id' => $express, 'minutes' => 15, 'distance_meters' => 4200,
        ]);

        $plans = $this->busAndMetroPlans();
        $firstLine = fn (array $plan): int => (int) DB::table('connections')->where('id', $plan['steps'][0]['connection_id'])->value('line_id');

        $this->assertSame($express, $firstLine($plans['fastest']));
        $this->assertSame([37, 450], [$plans['fastest']['minutes'], $plans['fastest']['cost']]);
        $this->assertSame((int) DB::table('lines')->where('code', '042')->value('id'), $firstLine($plans['cheapest']));
        $this->assertSame([47, 380], [$plans['cheapest']['minutes'], $plans['cheapest']['cost']]);
    }

    public function test_the_return_trip_spends_the_transfer_time_of_the_reverse_integration(): void
    {
        $plan = app(RoutePlanner::class)->plan($this->stop('Recife Antigo'), $this->stop('Boa Viagem'), [
            'modes' => ['bus', 'metro'], 'departure_at' => '2026-10-30T14:30:00-03:00',
        ])['fastest'];

        $this->assertSame(['Estação Recife', 'Estação Joana Bezerra', 'Boa Viagem'], $this->visitedStops($plan));
        $this->assertSame([0, 0, 3], array_column($plan['steps'], 'transfer_minutes'));
        $this->assertSame([0, 3, 7], array_column($plan['steps'], 'wait_minutes'));
        $this->assertSame(48, $plan['minutes']);
    }

    public function test_accessible_trips_avoid_inaccessible_integrations_and_stops_without_passing_a_stop_twice(): void
    {
        $detour = ['Estação Joana Bezerra', 'Afogados', 'Derby', 'Boa Vista', 'Recife Antigo'];
        DB::table('stops')->update(['accessible' => true]);
        DB::table('connections')->update(['accessible' => true, 'adapted' => true, 'has_stairs' => false]);
        $this->withoutLines('061');
        $this->assertContains('Estação Recife', $this->visitedStops($this->busAndMetroPlans(['accessibility_needs' => ['wheelchair']])['fastest']));
        DB::table('integrations')->where('stop_id', $this->stop('Estação Joana Bezerra'))->update(['accessible' => false]);

        $this->assertContains('Estação Recife', $this->visitedStops($this->busAndMetroPlans()['fastest']));
        $this->assertSame($detour, $this->visitedStops($this->busAndMetroPlans(['accessibility_needs' => ['wheelchair']])['fastest']));

        DB::table('integrations')->update(['accessible' => true]);
        DB::table('stops')->where('name', 'Estação Recife')->update(['accessible' => false]);

        $this->assertContains('Estação Recife', $this->visitedStops($this->busAndMetroPlans()['fastest']));
        $this->assertSame($detour, $this->visitedStops($this->busAndMetroPlans(['accessibility_needs' => ['wheelchair']])['fastest']));
    }

    private function stop(string $name): int
    {
        return (int) DB::table('stops')->where('name', $name)->value('id');
    }

    /**
     * Remove the connections of the given lines, which would otherwise offer another way around the case under test
     * (line 061 from Boa Viagem along the Via Mangue, the BRT beside line 081).
     */
    private function withoutLines(string ...$codes): void
    {
        DB::table('connections')->whereIn('line_id', DB::table('lines')->whereIn('code', $codes)->pluck('id'))->delete();
    }

    /**
     * Plans from Boa Viagem to Recife Antigo by bus and metro, leaving at 14:30.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    private function busAndMetroPlans(array $filters = []): array
    {
        return app(RoutePlanner::class)->plan($this->stop('Boa Viagem'), $this->stop('Recife Antigo'), $filters + [
            'modes' => ['bus', 'metro'], 'departure_at' => '2026-10-30T14:30:00-03:00',
        ]);
    }

    /**
     * Names of the stops a plan arrives at, in order.
     *
     * @param  array<string, mixed>  $plan
     * @return list<string>
     */
    private function visitedStops(array $plan): array
    {
        $names = DB::table('connections')->join('stops', 'stops.id', '=', 'connections.to_stop_id')
            ->whereIn('connections.id', array_column($plan['steps'], 'connection_id'))->pluck('stops.name', 'connections.id');

        return array_map(fn (array $step): string => $names[$step['connection_id']], $plan['steps']);
    }

    /** @return array<string, array<string, mixed>> */
    private function busPlans(string $departure): array
    {
        return app(RoutePlanner::class)->plan($this->stop('Cordeiro'), $this->stop('Recife Antigo'), [
            'modes' => ['bus'], 'departure_at' => $departure,
        ]);
    }
}
