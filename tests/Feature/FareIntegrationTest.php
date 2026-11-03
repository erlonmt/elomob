<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Integration;
use App\Models\Line;
use App\Models\Stop;
use App\Services\Routing\Plan;
use App\Services\Routing\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FareIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_changing_from_bus_to_metro_at_a_fare_integrated_terminal_charges_a_single_fare(): void
    {
        $plan = $this->busAndMetroPlans('Boa Viagem', 'Recife Antigo')['fastest'];

        $this->assertSame(['Estação Joana Bezerra', 'Estação Recife', 'Recife Antigo'], $this->visitedStops($plan));
        $this->assertSame([380, 0, 0], array_column($plan->steps, 'cost_cents'));
        $this->assertSame([false, true, false], array_column($plan->steps, 'fare_integrated'));
        $this->assertSame(380, $plan->cost);
        $this->assertSame(1, $plan->transfers);
    }

    public function test_a_cheaper_first_fare_is_topped_up_to_the_higher_one(): void
    {
        $plan = $this->busAndMetroPlans('Recife Antigo', 'Boa Viagem')['fastest'];

        $this->assertSame(['Estação Recife', 'Estação Joana Bezerra', 'Boa Viagem'], $this->visitedStops($plan));
        $this->assertSame([0, 160, 220], array_column($plan->steps, 'cost_cents'));
        $this->assertSame([false, false, true], array_column($plan->steps, 'fare_integrated'));
        $this->assertSame(380, $plan->cost);
    }

    public function test_an_integration_without_fare_integration_charges_the_full_fare(): void
    {
        DB::table('integrations')->update(['fare_integrated' => false]);

        $plan = $this->busAndMetroPlans('Boa Viagem', 'Recife Antigo')['fastest'];

        $this->assertSame([380, 160, 0], array_column($plan->steps, 'cost_cents'));
        $this->assertSame([false, false, false], array_column($plan->steps, 'fare_integrated'));
        $this->assertSame(540, $plan->cost);
    }

    public function test_walking_into_an_integrated_terminal_pays_the_full_fare(): void
    {
        $plan = $this->busAndMetroPlans('Casa Amarela', 'Estação Recife')['cheapest'];

        $this->assertSame(['Graças', 'Derby', 'Afogados', 'Estação Joana Bezerra', 'Estação Recife'], $this->visitedStops($plan));
        $this->assertSame([380, 0, 0, 0, 160], array_column($plan->steps, 'cost_cents'));
        $this->assertSame([false, false, false, false, false], array_column($plan->steps, 'fare_integrated'));
        $this->assertSame(540, $plan->cost);
    }

    /**
     * A cheaper ride reaching a stop on the same line can still cost more in the end: on the way from A to D, a taxi
     * (R$ 1,00) and a metro (R$ 1,60) arrive at X for R$ 2,60, while a bus (R$ 3,00) integrated with the same metro
     * arrives there for R$ 3,00. Integrating with a R$ 3,80 bus at X tops up R$ 2,20 on the first ride and only
     * R$ 0,80 on the second, so the costlier ride must be kept.
     */
    public function test_a_costlier_ride_that_paid_a_higher_fare_is_kept_for_the_next_integration(): void
    {
        $modes = DB::table('transport_modes')->pluck('id', 'code');
        [$a, $p, $q, $x, $d] = Stop::factory()->count(5)->create()->modelKeys();
        $line = fn (string $mode, int $fare): Line => Line::factory()->create([
            'transport_mode_id' => $modes[$mode], 'fare_cents' => $fare, 'starts_at' => '00:00:00', 'ends_at' => '23:59:00', 'frequency_minutes' => 1,
        ]);
        $feeder = $line('bus', 300);
        $metro = $line('metro', 160);
        $trunk = $line('bus', 380);
        Connection::factory()->create(['from_stop_id' => $a, 'to_stop_id' => $p, 'transport_mode_id' => $modes['taxi'], 'minutes' => 1, 'distance_meters' => 100, 'boarding_fare_cents' => 100]);
        Connection::factory()->onLine($metro)->create(['from_stop_id' => $p, 'to_stop_id' => $x, 'minutes' => 1, 'distance_meters' => 100]);
        Connection::factory()->onLine($feeder)->create(['from_stop_id' => $a, 'to_stop_id' => $q, 'minutes' => 5, 'distance_meters' => 1000]);
        Connection::factory()->onLine($metro)->create(['from_stop_id' => $q, 'to_stop_id' => $x, 'minutes' => 5, 'distance_meters' => 1000]);
        Connection::factory()->onLine($trunk)->create(['from_stop_id' => $x, 'to_stop_id' => $d, 'minutes' => 5, 'distance_meters' => 1000]);
        Integration::factory()->fareIntegrated()->create(['stop_id' => $q, 'from_mode_id' => $modes['bus'], 'to_mode_id' => $modes['metro'], 'transfer_minutes' => 0]);
        Integration::factory()->fareIntegrated()->create(['stop_id' => $x, 'from_mode_id' => $modes['metro'], 'to_mode_id' => $modes['bus'], 'transfer_minutes' => 0]);

        $plan = app(RoutePlanner::class)->plan($a, $d, ['modes' => ['bus', 'metro', 'taxi'], 'departure_at' => '2026-10-30T12:00:00-03:00'])['cheapest'];

        $this->assertSame([300, 0, 80], array_column($plan->steps, 'cost_cents'));
        $this->assertSame(380, $plan->cost);
    }

    private function stop(string $name): int
    {
        return (int) DB::table('stops')->where('name', $name)->value('id');
    }

    /**
     * Plans between two stops of the demonstration network by bus and metro, leaving at 14:30.
     *
     * @return array<string, Plan>
     */
    private function busAndMetroPlans(string $origin, string $destination): array
    {
        return app(RoutePlanner::class)->plan($this->stop($origin), $this->stop($destination), [
            'modes' => ['bus', 'metro'], 'departure_at' => '2026-10-30T14:30:00-03:00',
        ]);
    }

    /**
     * Names of the stops a plan arrives at, in order.
     *
     * @return list<string>
     */
    private function visitedStops(Plan $plan): array
    {
        $names = DB::table('connections')->join('stops', 'stops.id', '=', 'connections.to_stop_id')
            ->whereIn('connections.id', array_column($plan->steps, 'connection_id'))->pluck('stops.name', 'connections.id');

        return array_map(fn (array $step): string => $names[$step['connection_id']], $plan->steps);
    }
}
