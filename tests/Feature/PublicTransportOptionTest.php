<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Services\Routing\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicTransportOptionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_best_trip_by_bus_and_metro_is_offered_when_no_criterion_chose_one(): void
    {
        $plans = $this->plans(['bus', 'metro', 'bike', 'taxi', 'app']);

        $this->assertSame(['fastest', 'cheapest', 'transfers', 'sustainable', 'public_transport'], array_keys($plans));
        $this->assertSame(['taxi'], $this->modes($plans['fastest']));
        $this->assertSame(['walk', 'bike'], $this->modes($plans['cheapest']));
        $this->assertSame(['bus', 'metro', 'walk'], $this->modes($plans['public_transport']));
        $this->assertSame([47, 380], [$plans['public_transport']['minutes'], $plans['public_transport']['cost']]);
    }

    public function test_no_extra_option_when_a_criterion_already_chose_public_transport_or_none_runs(): void
    {
        $this->assertArrayNotHasKey('public_transport', $this->plans(['bus', 'metro']));
        $this->assertArrayNotHasKey('public_transport', $this->plans(['bike', 'taxi', 'app']));
    }

    /**
     * Plan Boa Viagem → Recife Antigo at 14:30 with the given modes.
     *
     * @param  list<'bus'|'metro'|'bike'|'taxi'|'app'>  $modes
     * @return array<string, array{minutes: int, cost: int, distance: int, transfers: int, impact: int, steps: list<array{connection_id: int}>}>
     */
    private function plans(array $modes): array
    {
        $stops = DB::table('stops')->whereIn('name', ['Boa Viagem', 'Recife Antigo'])->pluck('id', 'name');

        return app(RoutePlanner::class)->plan($stops['Boa Viagem'], $stops['Recife Antigo'], ['modes' => $modes, 'departure_at' => '2026-10-30T14:30:00-03:00']);
    }

    /**
     * The modes of a plan's steps, each once, in travel order.
     *
     * @param  array{steps: list<array{connection_id: int}>}  $plan
     * @return list<string>
     */
    private function modes(array $plan): array
    {
        $connections = Connection::with('mode')->findMany(array_column($plan['steps'], 'connection_id'))->keyBy('id');

        return array_values(array_unique(array_map(fn (array $step): string => $connections[$step['connection_id']]->mode->code, $plan['steps'])));
    }
}
