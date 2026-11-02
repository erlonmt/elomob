<?php

namespace Tests\Feature;

use App\Models\TripSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Runs migrations itself, so it uses DatabaseMigrations instead of RefreshDatabase: SQLite can only rebuild a table
 * that other rows reference (as it does to alter "connections") with foreign keys off, which a wrapping transaction prevents.
 * Before rolling back it keeps one line per stretch, as databases from before several lines could share a stretch.
 */
class MigrationUpgradeTest extends TestCase
{
    use DatabaseMigrations;

    protected bool $seed = true;

    public function test_upgrades_preserve_existing_accounts_favorites_searches_and_itineraries(): void
    {
        $migrationPaths = [
            'database/migrations/2026_10_31_012655_add_spatial_network_and_wait_times.php',
            'database/migrations/2026_10_31_205505_add_integrity_checks_and_foreign_key_indexes.php',
            'database/migrations/2026_10_31_212051_make_lines_the_source_of_connection_fares_and_modes.php',
            'database/migrations/2026_10_31_222451_add_transfer_minutes_to_itinerary_steps.php',
            'database/migrations/2026_10_31_225510_allow_several_lines_on_the_same_stretch.php',
            'database/migrations/2026_10_31_230854_move_search_departure_out_of_filters.php',
            'database/migrations/2026_11_02_004520_add_accessibility_needs_and_features.php',
            'database/migrations/2026_11_02_132510_add_fare_integration_to_integrations_and_steps.php',
            'database/migrations/2026_11_02_141210_create_network_versions_table.php',
        ];
        $firstLineOfEachStretch = DB::table('connections')->selectRaw('MIN(id)')->groupBy('from_stop_id', 'to_stop_id', 'transport_mode_id');
        DB::table('connections')->whereNotIn('id', $firstLineOfEachStretch)->delete();
        $this->artisan('migrate:rollback', ['--path' => $migrationPaths, '--step' => count($migrationPaths), '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $this->assertFalse(Schema::hasColumn('itinerary_steps', 'wait_minutes'));
        $this->assertFalse(Schema::hasIndex('itinerary_steps', ['connection_id']));
        $this->assertFalse(Schema::hasColumn('users', 'accessibility_needs'));
        $this->assertFalse(Schema::hasColumn('stops', 'tactile_paving'));
        $this->assertFalse(Schema::hasColumn('integrations', 'fare_integrated'));
        $this->assertFalse(Schema::hasTable('network_versions'));

        $user = User::factory()->create();
        $user->forceFill(['accessible_only' => true, 'avoid_stairs' => true, 'transport_modes' => ['taxi']])->save();
        $origin = (int) DB::table('stops')->where('name', 'Boa Viagem')->value('id');
        $destination = (int) DB::table('stops')->where('name', 'Recife Antigo')->value('id');
        $connection = DB::table('connections')->where('from_stop_id', $origin)->where('to_stop_id', $destination)
            ->where('transport_mode_id', DB::table('transport_modes')->where('code', 'taxi')->value('id'))->first();
        $favoriteId = DB::table('saved_places')->insertGetId([
            'user_id' => $user->id, 'stop_id' => $origin, 'label' => 'Casa', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $searchId = DB::table('trip_searches')->insertGetId([
            'user_id' => $user->id, 'session_hash' => str_repeat('b', 64), 'origin_id' => $origin, 'destination_id' => $destination,
            'filters' => json_encode(['origin_id' => $origin, 'destination_id' => $destination, 'modes' => ['taxi'], 'departure_at' => '2026-10-30T14:30:00-03:00', 'accessible_only' => true, 'avoid_stairs' => false, 'adapted_only' => false]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $itineraryId = DB::table('itineraries')->insertGetId([
            'trip_search_id' => $searchId, 'criterion' => 'fastest', 'total_minutes' => $connection->minutes,
            'total_cost_cents' => $connection->boarding_fare_cents, 'total_distance_meters' => $connection->distance_meters, 'transfers' => 0,
        ]);
        $stepId = DB::table('itinerary_steps')->insertGetId([
            'itinerary_id' => $itineraryId, 'connection_id' => $connection->id, 'sequence' => 1,
            'minutes' => $connection->minutes, 'cost_cents' => $connection->boarding_fare_cents, 'distance_meters' => $connection->distance_meters,
        ]);
        $existingRows = [];
        foreach (['users' => $user->id, 'saved_places' => $favoriteId, 'trip_searches' => $searchId, 'itineraries' => $itineraryId, 'itinerary_steps' => $stepId] as $table => $id) {
            $existingRows[$table] = (array) DB::table($table)->find($id);
        }
        unset($existingRows['trip_searches']['filters'], $existingRows['users']['accessible_only'], $existingRows['users']['avoid_stairs'], $existingRows['users']['adapted_only']);
        $stopIds = DB::table('stops')->orderBy('id')->pluck('id')->all();
        $connections = DB::table('connections')->orderBy('id')->get(['id', 'from_stop_id', 'to_stop_id', 'transport_mode_id', 'line_id']);

        $this->artisan('migrate', ['--path' => $migrationPaths, '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $this->assertTrue(Schema::hasIndex('itinerary_steps', ['connection_id']));
        $this->assertSame(0, DB::table('connections')->whereNotNull('line_id')->whereNotNull('boarding_fare_cents')->count());
        $this->assertSame(0, DB::table('connections')->whereNull('line_id')->whereNull('boarding_fare_cents')->count());
        $search = DB::table('trip_searches')->find($searchId);
        $this->assertSame('2026-10-30 17:30:00', $search->departure_at);
        $this->assertSame(['modes' => ['taxi'], 'accessibility_needs' => ['wheelchair']], json_decode($search->filters, true));
        $this->assertSame(['wheelchair', 'reduced_mobility'], json_decode(DB::table('users')->where('id', $user->id)->value('accessibility_needs'), true));
        foreach (['accessible_only', 'avoid_stairs', 'adapted_only'] as $column) {
            $this->assertFalse(Schema::hasColumn('users', $column));
        }
        $this->assertTrue(Schema::hasColumns('stops', ['tactile_paving', 'visual_signs']));
        $this->assertTrue(Schema::hasColumns('connections', ['audible_signals', 'visual_signs']));
        $this->assertSame(0, DB::table('integrations')->where('fare_integrated', true)->count());
        $version = DB::table('network_versions')->value('version');
        $this->assertNotNull($version);
        if (DB::getDriverName() === 'pgsql') {
            $this->assertSame(0, DB::table('stops')->whereNull('location')->count());
            $this->assertSame(0, DB::table('connections')->whereNull('path')->count());
            $mismatchedPaths = DB::table('connections as c')->join('stops as a', 'a.id', '=', 'c.from_stop_id')->join('stops as b', 'b.id', '=', 'c.to_stop_id')
                ->whereRaw('NOT ST_Equals(c.path, ST_MakeLine(a.location::geometry, b.location::geometry))')->count();
            $this->assertSame(0, $mismatchedPaths);
        }
        $this->seed();

        foreach ($existingRows as $table => $row) {
            $this->assertEquals($row, (array) DB::table($table)->select(array_keys($row))->find($row['id']));
        }
        $this->assertSame($stopIds, DB::table('stops')->orderBy('id')->pluck('id')->all());
        $this->assertEquals($connections, DB::table('connections')->whereIn('id', $connections->pluck('id'))->orderBy('id')->get(['id', 'from_stop_id', 'to_stop_id', 'transport_mode_id', 'line_id']));
        $this->assertDatabaseCount('connections', 52);
        $this->assertDatabaseHas('itinerary_steps', ['id' => $stepId, 'wait_minutes' => 0, 'transfer_minutes' => 0, 'fare_integrated' => false]);
        $this->assertSame(2, DB::table('integrations')->where('fare_integrated', true)->count());
        $this->assertNotSame($version, DB::table('network_versions')->value('version'));

        // DatabaseMigrations rolls every migration back after the test, which again needs one line per stretch.
        DB::table('connections')->whereNotIn('id', $firstLineOfEachStretch)->delete();
    }

    public function test_rolling_back_accessibility_needs_turns_on_the_switches_that_cover_them(): void
    {
        $needsBySwitches = [
            'no need' => [[], [false, false, false]],
            'wheelchair' => [['wheelchair'], [true, true, true]],
            'reduced mobility and visual' => [['reduced_mobility', 'visual'], [false, true, false]],
            'hearing' => [['hearing'], [false, false, false]],
        ];
        $users = [];
        foreach ($needsBySwitches as $case => [$needs]) {
            $users[$case] = User::factory()->create(['accessibility_needs' => $needs])->id;
        }
        $search = TripSearch::factory()->create(['filters' => ['modes' => ['bus'], 'accessibility_needs' => ['reduced_mobility']]]);

        $this->artisan('migrate:rollback', ['--path' => [
            'database/migrations/2026_11_02_004520_add_accessibility_needs_and_features.php',
            'database/migrations/2026_11_02_132510_add_fare_integration_to_integrations_and_steps.php',
            'database/migrations/2026_11_02_141210_create_network_versions_table.php',
        ], '--step' => 3, '--force' => true, '--no-interaction' => true])->assertExitCode(0);

        $this->assertFalse(Schema::hasColumn('users', 'accessibility_needs'));
        foreach ($needsBySwitches as $case => [, $switches]) {
            $user = DB::table('users')->find($users[$case]);
            $this->assertSame($switches, [(bool) $user->accessible_only, (bool) $user->avoid_stairs, (bool) $user->adapted_only], $case);
        }
        $this->assertEquals(
            ['modes' => ['bus'], 'accessible_only' => false, 'avoid_stairs' => true, 'adapted_only' => false],
            json_decode(DB::table('trip_searches')->where('id', $search->id)->value('filters'), true),
        );

        // DatabaseMigrations rolls the remaining migrations back after the test, which needs one line per stretch.
        DB::table('connections')->whereNotIn('id', DB::table('connections')->selectRaw('MIN(id)')->groupBy('from_stop_id', 'to_stop_id', 'transport_mode_id'))->delete();
    }
}
