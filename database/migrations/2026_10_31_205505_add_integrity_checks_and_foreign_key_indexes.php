<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foreign key columns that had no index of their own, by table. Neither PostgreSQL nor SQLite indexes
     * the referencing side of a foreign key, so joins and the RESTRICT checks on delete scanned these tables.
     *
     * @var array<string, list<string>>
     */
    private const array FOREIGN_KEYS = [
        'stops' => ['transport_mode_id'],
        'lines' => ['transport_mode_id'],
        'connections' => ['to_stop_id', 'transport_mode_id', 'line_id'],
        'integrations' => ['from_mode_id', 'to_mode_id'],
        'saved_places' => ['stop_id'],
        'trip_searches' => ['origin_id', 'destination_id'],
        'itinerary_steps' => ['connection_id'],
    ];

    /**
     * PostgreSQL CHECK constraints, by table and name. Laravel's "unsigned" columns are plain signed integers
     * in PostgreSQL, so these rules are what keeps invalid values out: durations, distances, frequencies and
     * positions are positive, money and counts are never negative, and a step's wait is part of its duration.
     * The criteria are the RouteCriterion values as of this migration; adding one needs a new migration.
     *
     * @var array<string, array<string, string>>
     */
    private const array CHECKS = [
        'lines' => [
            'lines_valid' => 'frequency_minutes > 0 AND fare_cents >= 0',
        ],
        'stops' => [
            'stops_bike_counts' => 'bikes_available >= 0 AND docks_available >= 0',
        ],
        'integrations' => [
            'integrations_valid' => 'transfer_minutes >= 0',
        ],
        'itineraries' => [
            'itineraries_valid' => 'total_minutes > 0 AND total_distance_meters > 0 AND total_cost_cents >= 0 AND transfers >= 0',
            'itineraries_criterion' => "criterion IN ('fastest', 'cheapest', 'transfers', 'sustainable')",
        ],
        'itinerary_steps' => [
            'itinerary_steps_valid' => 'sequence > 0 AND minutes > 0 AND distance_meters > 0 AND cost_cents >= 0 AND wait_minutes BETWEEN 0 AND minutes',
        ],
        'users' => [
            'users_preferred_route' => "preferred_route IN ('fastest', 'cheapest', 'transfers', 'sustainable')",
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::FOREIGN_KEYS as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->index($column);
                }
            });
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::CHECKS as $tableName => $checks) {
            foreach ($checks as $constraint => $condition) {
                DB::statement("ALTER TABLE {$tableName} ADD CONSTRAINT {$constraint} CHECK ({$condition})");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach (self::CHECKS as $tableName => $checks) {
                foreach (array_keys($checks) as $constraint) {
                    DB::statement("ALTER TABLE {$tableName} DROP CONSTRAINT {$constraint}");
                }
            }
        }

        foreach (self::FOREIGN_KEYS as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    $table->dropIndex([$column]);
                }
            });
        }
    }
};
