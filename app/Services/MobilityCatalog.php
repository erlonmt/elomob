<?php

namespace App\Services;

use App\Enums\TransportModeCode;
use App\Http\Resources\IntegrationResource;
use App\Http\Resources\LineResource;
use App\Http\Resources\StopResource;
use App\Models\Connection;
use App\Models\Integration;
use App\Models\Line;
use App\Models\Stop;
use App\Models\TransportMode;
use App\Services\Routing\LineRoutes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MobilityCatalog
{
    /**
     * Get the public catalog. It is cached as plain arrays (never models), so the cached payload
     * stays serializable and independent of the model classes.
     *
     * @return array{stops: list<array<string, mixed>>, modes: list<array<string, mixed>>, lines: list<array<string, mixed>>, integrations: list<array<string, mixed>>}
     */
    public function all(): array
    {
        return Cache::remember('mobility:catalog:v5', 30, function (): array {
            $stops = Stop::with('mode')->orderBy('name')->get();
            $routes = LineRoutes::fromStretches(Connection::whereNotNull('line_id')->get(['line_id', 'from_stop_id', 'to_stop_id', 'minutes'])->toArray());
            $names = $stops->pluck('name', 'id');

            return [
                'stops' => StopResource::collection($stops)->resolve(),
                'modes' => TransportMode::whereIn('code', TransportModeCode::selectableValues())->orderBy('id')->get()->toArray(),
                'lines' => Line::with('mode')->orderBy('id')->get()
                    ->map(fn (Line $line): array => (new LineResource($line, $this->directions($line, $routes, $names)))->resolve())
                    ->all(),
                'integrations' => IntegrationResource::collection(Integration::with(['stop', 'fromMode', 'toMode'])->orderBy('id')->get())->resolve(),
            ];
        });
    }

    /**
     * The ways of a line with the names of their stops, the way toward the stop the line's name ends with first
     * ("Cordeiro → Recife Antigo" lists the way to Recife Antigo first).
     *
     * @param  Collection<int, string>  $names  Stop names by id.
     * @return list<array{to: string, stops: list<array{id: int, name: string, minutes: int}>}>
     */
    private function directions(Line $line, LineRoutes $routes, Collection $names): array
    {
        $ways = array_map(fn (array $stops): array => [
            'to' => $names[$stops[array_key_last($stops)]['stop']],
            'stops' => array_map(fn (array $stop): array => ['id' => $stop['stop'], 'name' => $names[$stop['stop']], 'minutes' => $stop['minutes']], $stops),
        ], $routes->directions($line->id));
        $destination = str_contains($line->name, '→') ? trim(Str::afterLast($line->name, '→')) : '';
        usort($ways, fn (array $first, array $second): int => (int) ! str_starts_with($destination, $first['to']) <=> (int) ! str_starts_with($destination, $second['to']));

        return $ways;
    }

    /**
     * Keep the catalog items matching the optional mode and the optional text searched in the name or code.
     *
     * @param  list<array<string, mixed>>  $items
     * @param  array{q?: ?string, mode?: ?string}  $filters
     * @return list<array<string, mixed>>
     */
    public function filter(array $items, array $filters): array
    {
        return collect($items)->filter(function (array $item) use ($filters): bool {
            if (! empty($filters['mode']) && $item['mode'] !== $filters['mode']) {
                return false;
            }

            return ! isset($filters['q']) || $filters['q'] === '' || mb_stripos($item['name'].' '.($item['code'] ?? ''), $filters['q']) !== false;
        })->values()->all();
    }
}
