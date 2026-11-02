<?php

namespace App\Services;

use App\Enums\TransportModeCode;
use App\Http\Resources\IntegrationResource;
use App\Http\Resources\LineResource;
use App\Http\Resources\StopResource;
use App\Models\Integration;
use App\Models\Line;
use App\Models\Stop;
use App\Models\TransportMode;
use Illuminate\Support\Facades\Cache;

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
        return Cache::remember('mobility:catalog:v4', 30, fn (): array => [
            'stops' => StopResource::collection(Stop::with('mode')->orderBy('name')->get())->resolve(),
            'modes' => TransportMode::whereIn('code', TransportModeCode::selectableValues())->orderBy('id')->get()->toArray(),
            'lines' => LineResource::collection(Line::with('mode')->orderBy('id')->get())->resolve(),
            'integrations' => IntegrationResource::collection(Integration::with(['stop', 'fromMode', 'toMode'])->orderBy('id')->get())->resolve(),
        ]);
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
