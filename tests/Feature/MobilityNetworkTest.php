<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Stop;
use App\Services\MobilityNetwork;
use App\Services\RoutePlanner;
use Closure;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MobilityNetworkTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * A change to each network table, covering inserts, updates and deletes.
     *
     * @return array<string, array{Closure(): mixed}>
     */
    public static function networkChanges(): array
    {
        return [
            'mode updated' => [fn () => DB::table('transport_modes')->where('code', 'bus')->update(['color' => '#000000'])],
            'stop inserted' => [fn () => Stop::factory()->create()],
            'stop updated' => [fn () => DB::table('stops')->where('name', 'Afogados')->update(['visual_signs' => true])],
            'line updated' => [fn () => DB::table('lines')->where('code', '042')->update(['fare_cents' => 400])],
            'connection inserted' => [fn () => DB::table('connections')->insert([
                'from_stop_id' => DB::table('stops')->where('name', 'Boa Viagem')->value('id'),
                'to_stop_id' => DB::table('stops')->where('name', 'Pina')->value('id'),
                'transport_mode_id' => DB::table('transport_modes')->where('code', 'walk')->value('id'),
                'minutes' => 60, 'distance_meters' => 4500, 'boarding_fare_cents' => 0,
            ])],
            'connection deleted' => [fn () => DB::table('connections')->where('boarding_fare_cents', 1850)->delete()],
            'integration updated' => [fn () => DB::table('integrations')->update(['transfer_minutes' => 4])],
            'integration deleted' => [fn () => DB::table('integrations')->where('fare_integrated', false)->delete()],
        ];
    }

    /**
     * @param  Closure(): mixed  $change
     */
    #[DataProvider('networkChanges')]
    public function test_any_change_to_the_network_renews_its_version(Closure $change): void
    {
        $version = $this->version();

        $change();

        $this->assertNotSame($version, $this->version());
    }

    public function test_the_planner_reads_the_network_from_the_cache_until_it_changes(): void
    {
        $this->assertSame(34, $this->fastestBusMinutes());

        DB::enableQueryLog();
        $this->assertSame(34, $this->fastestBusMinutes());
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('network_versions', $queries[0]);

        DB::table('connections')->update(['minutes' => DB::raw('minutes + 1')]);

        $this->assertSame(39, $this->fastestBusMinutes());
    }

    public function test_planning_fails_rather_than_cache_a_network_without_a_version(): void
    {
        DB::table('network_versions')->delete();

        $this->expectException(RecordsNotFoundException::class);
        $this->fastestBusMinutes();
    }

    public function test_the_network_is_loaded_with_only_the_connections_of_the_chosen_modes(): void
    {
        $connections = app(MobilityNetwork::class)->load(['bus', 'walk'])['connections'];
        $expected = DB::table('connections')->join('transport_modes', 'transport_modes.id', '=', 'connections.transport_mode_id')
            ->whereIn('transport_modes.code', ['bus', 'walk'])->count();

        $this->assertSame(['bus', 'walk'], $connections->map(fn (Connection $connection): string => $connection->mode->code)->unique()->sort()->values()->all());
        $this->assertCount($expected, $connections);
    }

    public function test_the_cached_network_holds_plain_arrays(): void
    {
        $this->fastestBusMinutes();

        $cached = Cache::get('mobility:network:'.$this->version());
        $this->assertIsArray($cached);
        $this->assertSame($cached, json_decode(json_encode($cached, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR));
        $this->assertCount(580, $cached['connections']);
    }

    private function version(): string
    {
        return (string) DB::table('network_versions')->value('version');
    }

    /**
     * Minutes of the fastest bus trip from Cordeiro to Recife Antigo leaving at 14:31: 14 waiting for line 081 and 32
     * on board.
     */
    private function fastestBusMinutes(): int
    {
        $stops = once(fn (): array => DB::table('stops')->whereIn('name', ['Cordeiro', 'Recife Antigo'])->pluck('id', 'name')->all());

        return app(RoutePlanner::class)->plan($stops['Cordeiro'], $stops['Recife Antigo'], [
            'modes' => ['bus'], 'departure_at' => '2026-10-30T14:31:00-03:00',
        ])['fastest']['minutes'];
    }
}
