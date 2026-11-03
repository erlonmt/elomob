<?php

namespace Tests\Feature;

use App\Enums\RouteCriterion;
use App\Models\Itinerary;
use App\Models\ItineraryStep;
use App\Models\Line;
use App\Models\TripSearch;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * Changes each CHECK constraint must reject, applied to the first row of the table, with the constraint expected.
     * The first seeded connection is on line 042.
     *
     * @var array<string, array{string, array<string, int|string|null>, string}>
     */
    private const array VALUES_OUTSIDE_THE_DOMAIN = [
        'line without frequency' => ['lines', ['frequency_minutes' => 0], 'lines_valid'],
        'negative line fare' => ['lines', ['fare_cents' => -1], 'lines_valid'],
        'negative bikes' => ['stops', ['bikes_available' => -1], 'stops_bike_counts'],
        'negative docks' => ['stops', ['docks_available' => -1], 'stops_bike_counts'],
        'negative transfer time' => ['integrations', ['transfer_minutes' => -1], 'integrations_valid'],
        'itinerary without duration' => ['itineraries', ['total_minutes' => 0], 'itineraries_valid'],
        'itinerary without distance' => ['itineraries', ['total_distance_meters' => 0], 'itineraries_valid'],
        'negative itinerary cost' => ['itineraries', ['total_cost_cents' => -1], 'itineraries_valid'],
        'negative transfers' => ['itineraries', ['transfers' => -1], 'itineraries_valid'],
        'unknown criterion' => ['itineraries', ['criterion' => 'slowest'], 'itineraries_criterion'],
        'step at position zero' => ['itinerary_steps', ['sequence' => 0], 'itinerary_steps_valid'],
        'step without duration' => ['itinerary_steps', ['minutes' => 0, 'wait_minutes' => 0], 'itinerary_steps_valid'],
        'step without distance' => ['itinerary_steps', ['distance_meters' => 0], 'itinerary_steps_valid'],
        'negative step cost' => ['itinerary_steps', ['cost_cents' => -1], 'itinerary_steps_valid'],
        'negative wait' => ['itinerary_steps', ['wait_minutes' => -1], 'itinerary_steps_valid'],
        'wait longer than the step' => ['itinerary_steps', ['minutes' => 5, 'wait_minutes' => 6], 'itinerary_steps_valid'],
        'negative transfer time on a step' => ['itinerary_steps', ['transfer_minutes' => -1], 'itinerary_steps_valid'],
        'wait and transfer longer than the step' => ['itinerary_steps', ['minutes' => 5, 'wait_minutes' => 3, 'transfer_minutes' => 3], 'itinerary_steps_valid'],
        'unknown preferred route' => ['users', ['preferred_route' => 'slowest'], 'users_preferred_route'],
        'unknown accessibility need' => ['users', ['accessibility_needs' => '["flying"]'], 'users_accessibility_needs'],
        'accessibility needs outside a list' => ['users', ['accessibility_needs' => '{"visual": true}'], 'users_accessibility_needs'],
        'line connection with its own fare' => ['connections', ['boarding_fare_cents' => 380], 'connections_fare_source'],
        'connection without a line nor a fare' => ['connections', ['line_id' => null], 'connections_fare_source'],
        'second network version' => ['network_versions', ['id' => 2], 'network_versions_single_row'],
    ];

    public function test_every_foreign_key_is_the_leading_column_of_an_index(): void
    {
        $unindexed = [];
        foreach (array_column(Schema::getTables(), 'name') as $table) {
            $indexedColumns = array_column(Schema::getIndexes($table), 'columns');
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $isIndexed = array_any($indexedColumns, fn (array $columns): bool => array_slice($columns, 0, count($foreignKey['columns'])) === $foreignKey['columns']);
                if (! $isIndexed) {
                    $unindexed[] = $table.'.'.implode(',', $foreignKey['columns']);
                }
            }
        }

        $this->assertSame([], $unindexed);
    }

    public function test_a_connection_on_a_line_always_has_the_mode_of_the_line(): void
    {
        $modes = DB::table('transport_modes')->pluck('id', 'code');
        $line = DB::table('lines')->where('code', '042')->first();
        $connections = DB::table('connections')->where('line_id', $line->id);

        try {
            DB::transaction(fn (): int => (clone $connections)->update(['transport_mode_id' => $modes['metro']]));
            $this->fail('A bus line connection was given the metro mode.');
        } catch (QueryException) {
            $this->assertSame([$modes['bus']], (clone $connections)->distinct()->pluck('transport_mode_id')->all());
        }

        DB::table('lines')->where('id', $line->id)->update(['transport_mode_id' => $modes['metro']]);

        $this->assertSame([$modes['metro']], (clone $connections)->distinct()->pluck('transport_mode_id')->all());
    }

    public function test_several_lines_can_serve_the_same_stretch_once_each(): void
    {
        $stretch = $this->stretch('042');
        $express = Line::factory()->create(['transport_mode_id' => $stretch['transport_mode_id']]);

        DB::table('connections')->insert($stretch + ['line_id' => $express->id, 'minutes' => 15, 'distance_meters' => 4200]);
        $this->assertSame(2, DB::table('connections')->where($stretch)->count());

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('connections')->insert($stretch + ['line_id' => $express->id, 'minutes' => 12, 'distance_meters' => 4200]);
    }

    public function test_postgresql_keeps_one_connection_without_a_line_per_stretch_and_mode(): void
    {
        $this->skipUnlessPostgreSql();
        $taxi = DB::table('connections')->where('transport_mode_id', DB::table('transport_modes')->where('code', 'taxi')->value('id'))->first();

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('connections')->insert([
            'from_stop_id' => $taxi->from_stop_id, 'to_stop_id' => $taxi->to_stop_id, 'transport_mode_id' => $taxi->transport_mode_id,
            'minutes' => 20, 'distance_meters' => 7800, 'boarding_fare_cents' => 1500,
        ]);
    }

    public function test_postgresql_stores_json_columns_as_jsonb(): void
    {
        $this->skipUnlessPostgreSql();

        $this->assertSame('jsonb', Schema::getColumnType('trip_searches', 'filters'));
        $this->assertSame('jsonb', Schema::getColumnType('users', 'transport_modes'));
        $this->assertSame('jsonb', Schema::getColumnType('users', 'accessibility_needs'));
    }

    public function test_postgresql_rejects_values_outside_the_domain(): void
    {
        $this->skipUnlessPostgreSql();
        User::factory()->create();
        ItineraryStep::factory()->create();

        foreach (self::VALUES_OUTSIDE_THE_DOMAIN as $case => [$table, $changes, $constraint]) {
            try {
                DB::transaction(fn (): int => DB::table($table)->where('id', DB::table($table)->min('id'))->update($changes));
                $this->fail("The {$case} case was accepted.");
            } catch (QueryException $exception) {
                $this->assertStringContainsString("check constraint \"{$constraint}\"", $exception->getMessage(), $case);
            }
        }
    }

    public function test_postgresql_accepts_every_route_criterion(): void
    {
        $this->skipUnlessPostgreSql();
        $search = TripSearch::factory()->create();

        foreach (RouteCriterion::cases() as $criterion) {
            Itinerary::factory()->for($search)->optimizedFor($criterion)->create();
            $user = User::factory()->create();
            $user->forceFill(['preferred_route' => $criterion])->save();

            $this->assertSame($criterion, $user->fresh()->preferred_route);
        }

        $this->assertSame(RouteCriterion::cases(), $search->itineraries()->pluck('criterion')->all());
    }

    /**
     * Stops and mode of the first seeded connection on the given line.
     *
     * @return array{from_stop_id: int, to_stop_id: int, transport_mode_id: int}
     */
    private function stretch(string $lineCode): array
    {
        $connection = DB::table('connections')->where('line_id', DB::table('lines')->where('code', $lineCode)->value('id'))->orderBy('id')->first();

        return ['from_stop_id' => $connection->from_stop_id, 'to_stop_id' => $connection->to_stop_id, 'transport_mode_id' => $connection->transport_mode_id];
    }

    private function skipUnlessPostgreSql(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('CHECK constraints are created only on PostgreSQL.');
        }
    }
}
