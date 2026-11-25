<?php

namespace App\Services\Planning;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Carrega a rede do banco e a guarda no cache sob a versão atual. Gatilhos no
 * PostgreSQL renovam a versão a cada mudança na rede, então o cache nunca
 * precisa ser invalidado à mão.
 */
class NetworkRepository
{
    public function currentVersion(): string
    {
        return (string) DB::table('network_versions')->where('id', 1)->value('version');
    }

    public function current(): Network
    {
        $version = $this->currentVersion();
        $ttl = now()->addMinutes(config('elomob.network_cache_ttl_minutes'));

        $data = Cache::remember("elomob:network:{$version}", $ttl, fn () => $this->load($version)->toArray());

        return Network::fromArray($data);
    }

    private function load(string $version): Network
    {
        $modes = DB::table('transport_modes')->pluck('code', 'id')->all();

        $stops = [];
        foreach (DB::table('stops')->get(['id', 'accessible', 'tactile_paving', 'visual_signs']) as $stop) {
            $stops[$stop->id] = [
                'accessible' => (bool) $stop->accessible,
                'tactile_paving' => (bool) $stop->tactile_paving,
                'visual_signs' => (bool) $stop->visual_signs,
            ];
        }

        $lines = [];
        foreach (DB::table('lines')->get() as $line) {
            $lines[$line->id] = [
                'mode' => (int) $line->transport_mode_id,
                'starts_at' => self::minuteOfDay($line->starts_at),
                'ends_at' => self::minuteOfDay($line->ends_at),
                'frequency' => (int) $line->frequency_minutes,
                'fare' => (int) $line->fare_cents,
            ];
        }

        $departures = [];
        foreach (DB::table('connections')->orderBy('id')->get() as $c) {
            $departures[$c->from_stop_id][] = [
                'id' => (int) $c->id,
                'from' => (int) $c->from_stop_id,
                'to' => (int) $c->to_stop_id,
                'mode' => (int) $c->transport_mode_id,
                'line' => $c->line_id === null ? null : (int) $c->line_id,
                'minutes' => (int) $c->minutes,
                'distance' => (int) $c->distance_meters,
                'fare' => $c->boarding_fare_cents === null ? null : (int) $c->boarding_fare_cents,
                'accessible' => (bool) $c->accessible,
                'has_stairs' => (bool) $c->has_stairs,
                'adapted' => (bool) $c->adapted,
                'audible_signals' => (bool) $c->audible_signals,
                'visual_signs' => (bool) $c->visual_signs,
            ];
        }

        $integrations = [];
        foreach (DB::table('integrations')->get() as $i) {
            $integrations["{$i->stop_id}:{$i->from_mode_id}:{$i->to_mode_id}"] = [
                'minutes' => (int) $i->transfer_minutes,
                'accessible' => (bool) $i->accessible,
                'fare_integrated' => (bool) $i->fare_integrated,
            ];
        }

        return new Network($version, $modes, $stops, $lines, $departures, $integrations);
    }

    private static function minuteOfDay(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
