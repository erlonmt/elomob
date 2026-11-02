<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The requested departure decides a search's results, so it moves from the "filters" JSON to its own column, in UTC
 * like the other timestamps (empty when the trip leaves at the time of the search, its created_at). "filters" keeps
 * only the search options and no longer repeats the origin and destination columns. On PostgreSQL the JSON columns
 * become jsonb, the binary JSON type that validates and normalizes what it stores.
 */
return new class extends Migration
{
    /**
     * Time zone that departures without an offset were requested in.
     */
    private const string NETWORK_TIMEZONE = 'America/Recife';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trip_searches', function (Blueprint $table): void {
            $table->timestamp('departure_at')->nullable();
        });

        DB::table('trip_searches')->select(['id', 'filters'])->lazyById()->each(function (object $search): void {
            $filters = json_decode($search->filters, true, 512, JSON_THROW_ON_ERROR);
            $departureAt = isset($filters['departure_at'])
                ? CarbonImmutable::parse($filters['departure_at'], self::NETWORK_TIMEZONE)->utc()->format('Y-m-d H:i:s')
                : null;
            unset($filters['origin_id'], $filters['destination_id'], $filters['departure_at']);

            DB::table('trip_searches')->where('id', $search->id)->update([
                'departure_at' => $departureAt,
                'filters' => json_encode((object) $filters, JSON_THROW_ON_ERROR),
            ]);
        });

        $this->convertJsonColumnsTo('jsonb');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->convertJsonColumnsTo('json');

        DB::table('trip_searches')->select(['id', 'origin_id', 'destination_id', 'departure_at', 'filters'])->lazyById()->each(function (object $search): void {
            $filters = ['origin_id' => (int) $search->origin_id, 'destination_id' => (int) $search->destination_id]
                + json_decode($search->filters, true, 512, JSON_THROW_ON_ERROR);
            if ($search->departure_at !== null) {
                $filters['departure_at'] = CarbonImmutable::parse($search->departure_at, 'UTC')->setTimezone(self::NETWORK_TIMEZONE)->toIso8601String();
            }

            DB::table('trip_searches')->where('id', $search->id)->update(['filters' => json_encode($filters, JSON_THROW_ON_ERROR)]);
        });

        Schema::table('trip_searches', function (Blueprint $table): void {
            $table->dropColumn('departure_at');
        });
    }

    /**
     * Change the JSON columns to the given PostgreSQL type (other drivers keep them as text).
     */
    private function convertJsonColumnsTo(string $type): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['trip_searches' => 'filters', 'users' => 'transport_modes'] as $table => $column) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE {$type} USING {$column}::{$type}");
        }
    }
};
