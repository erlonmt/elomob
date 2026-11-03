<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Integration;
use App\Models\Line;
use App\Models\Stop;
use App\Models\TransportMode;
use App\Services\Routing\LineRoutes;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The mobility network the route planner searches. It is cached as plain arrays (never models) under the network
 * version, which database triggers renew whenever a network table changes, so a cached network is never stale and a
 * search reads a single row from the database instead of the whole network.
 */
class MobilityNetwork
{
    /**
     * How long a version stays cached. A changed network gets a new key, so the expiry only clears old versions.
     */
    private const int CACHE_SECONDS = 86400;

    /**
     * Columns the route planner needs from each network table.
     *
     * @var array<string, list<string>>
     */
    private const array COLUMNS = [
        'transport_modes' => ['id', 'code'],
        'stops' => ['id', 'accessible', 'tactile_paving', 'visual_signs', 'bikes_available', 'docks_available'],
        'lines' => ['id', 'starts_at', 'ends_at', 'frequency_minutes', 'fare_cents'],
        'connections' => ['id', 'from_stop_id', 'to_stop_id', 'transport_mode_id', 'line_id', 'minutes', 'distance_meters', 'boarding_fare_cents', 'accessible', 'has_stairs', 'adapted', 'audible_signals', 'visual_signs'],
        'integrations' => ['id', 'stop_id', 'from_mode_id', 'to_mode_id', 'transfer_minutes', 'accessible', 'fare_integrated'],
    ];

    /**
     * Load the network a search with the given modes may use: the stops keyed by id, the connections of those modes in
     * id order with their mode, line and stops, and the integrations keyed by "stop id:mode code arrived with:mode code
     * left with", and the order of the stops along each line. The cache keeps the whole network; only the chosen
     * connections become models.
     *
     * @param  list<string>  $modeCodes
     * @return array{stops: EloquentCollection<int, Stop>, connections: EloquentCollection<int, Connection>, integrations: EloquentCollection<string, Integration>, lineRoutes: LineRoutes}
     */
    public function load(array $modeCodes): array
    {
        $rows = $this->rows();
        $modes = TransportMode::hydrate($rows['transport_modes'])->keyBy('id');
        $chosenModes = $modes->filter(fn (TransportMode $mode): bool => in_array($mode->code, $modeCodes, true));
        $lines = Line::hydrate($rows['lines'])->keyBy('id');
        $stops = Stop::hydrate($rows['stops'])->keyBy('id');
        $chosenRows = array_values(array_filter($rows['connections'], fn (array $row): bool => $chosenModes->has($row['transport_mode_id'])));
        $connections = Connection::hydrate($chosenRows)->each(function (Connection $connection) use ($modes, $lines, $stops): void {
            $connection->setRelation('mode', $modes[$connection->transport_mode_id])
                ->setRelation('line', $connection->line_id === null ? null : $lines[$connection->line_id])
                ->setRelation('fromStop', $stops[$connection->from_stop_id])
                ->setRelation('toStop', $stops[$connection->to_stop_id]);
        });
        $integrations = Integration::hydrate($rows['integrations'])->keyBy(
            fn (Integration $integration): string => $integration->stop_id.':'.$modes[$integration->from_mode_id]->code.':'.$modes[$integration->to_mode_id]->code,
        );

        return ['stops' => $stops, 'connections' => $connections, 'integrations' => $integrations, 'lineRoutes' => LineRoutes::fromStretches($rows['connections'])];
    }

    /**
     * The rows of every network table, from the cache of the current network version. The version row must exist:
     * without it, no change would ever leave the cached network.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function rows(): array
    {
        $version = DB::table('network_versions')->sole(['version'])->version;

        return Cache::remember("mobility:network:{$version}", self::CACHE_SECONDS, fn (): array => collect(self::COLUMNS)
            ->map(fn (array $columns, string $table): array => DB::table($table)->orderBy('id')->get($columns)->map(fn (object $row): array => (array) $row)->all())
            ->all());
    }
}
