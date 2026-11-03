<?php

namespace Tests\Feature;

use App\Enums\RouteCriterion;
use App\Models\Connection;
use App\Models\Integration;
use App\Models\Itinerary;
use App\Models\ItineraryStep;
use App\Models\Line;
use App\Models\SavedPlace;
use App\Models\Stop;
use App\Models\TransportMode;
use App\Models\TripSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobilityModelsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_network_models_cast_columns_to_native_types(): void
    {
        $stop = Stop::factory()->bikeStation(3, 5)->create(['latitude' => '-8.0630000', 'longitude' => '-34.8710000']);
        $connection = Connection::factory()->withStairs()->create(['minutes' => '12']);

        $stop = $stop->fresh();
        $this->assertSame(-8.063, $stop->latitude);
        $this->assertSame(-34.871, $stop->longitude);
        $this->assertTrue($stop->accessible);
        $this->assertTrue($stop->tactile_paving);
        $this->assertTrue($stop->visual_signs);
        $this->assertSame(3, $stop->bikes_available);
        $this->assertSame(5, $stop->docks_available);
        $connection = $connection->fresh();
        $this->assertSame(12, $connection->minutes);
        $this->assertTrue($connection->has_stairs);
        $this->assertFalse($connection->accessible);
        $this->assertTrue($connection->audible_signals);
        $this->assertTrue($connection->visual_signs);
        $this->assertNull($connection->line_id);
    }

    public function test_network_relationships_link_modes_stops_lines_connections_and_integrations(): void
    {
        $bus = TransportMode::factory()->create();
        $metro = TransportMode::factory()->create();
        $line = Line::factory()->for($bus, 'mode')->create();
        $origin = Stop::factory()->for($bus, 'mode')->create();
        $destination = Stop::factory()->for($metro, 'mode')->create();
        $connection = Connection::factory()->onLine($line)->create(['from_stop_id' => $origin->id, 'to_stop_id' => $destination->id]);
        $integration = Integration::factory()->create(['stop_id' => $destination->id, 'from_mode_id' => $bus->id, 'to_mode_id' => $metro->id]);

        $this->assertTrue($connection->fromStop->is($origin));
        $this->assertTrue($connection->toStop->is($destination));
        $this->assertTrue($connection->mode->is($bus));
        $this->assertTrue($connection->line->is($line));
        $this->assertNull($connection->boarding_fare_cents);
        $this->assertSame($line->fare_cents, $connection->fareCents());
        $this->assertTrue($origin->mode->is($bus));
        $this->assertTrue($line->mode->is($bus));
        $this->assertSame([$origin->id], $bus->stops->modelKeys());
        $this->assertSame([$line->id], $bus->lines->modelKeys());
        $this->assertTrue($integration->stop->is($destination));
        $this->assertTrue($integration->fromMode->is($bus));
        $this->assertTrue($integration->toMode->is($metro));
    }

    public function test_users_own_their_saved_places_and_trip_searches(): void
    {
        $user = User::factory()->create();
        $favorite = SavedPlace::factory()->for($user)->create();
        $search = TripSearch::factory()->for($user)->create();
        TripSearch::factory()->forGuestSession('another-session')->create();

        $this->assertSame([$favorite->id], $user->savedPlaces->modelKeys());
        $this->assertSame([$search->id], $user->tripSearches->modelKeys());
        $this->assertTrue($favorite->user->is($user));
        $this->assertInstanceOf(Stop::class, $favorite->stop);
        $this->assertTrue($search->user->is($user));
    }

    public function test_users_keep_their_accessibility_needs_as_a_list_that_starts_empty(): void
    {
        $user = User::create(['name' => 'Conta nova', 'email' => 'nova@elomob.test', 'password' => 'password']);
        $this->assertSame([], $user->fresh()->accessibility_needs);

        $user->update(['accessibility_needs' => ['wheelchair', 'hearing']]);
        $this->assertSame(['wheelchair', 'hearing'], $user->fresh()->accessibility_needs);
    }

    public function test_trip_searches_decode_filters_and_never_expose_the_session_hash(): void
    {
        $search = TripSearch::factory()->create()->fresh();

        $this->assertSame(['modes', 'accessibility_needs'], array_keys($search->filters));
        $this->assertSame([], $search->filters['accessibility_needs']);
        $this->assertSame(['bus', 'metro'], $search->filters['modes']);
        $this->assertNull($search->departure_at);
        $this->assertNotSame($search->origin_id, $search->destination_id);
        $this->assertTrue($search->origin->is(Stop::find($search->origin_id)));
        $this->assertTrue($search->destination->is(Stop::find($search->destination_id)));
        $this->assertSame(64, strlen($search->session_hash));
        $this->assertArrayNotHasKey('session_hash', $search->toArray());
        $this->assertJson(DB::table('trip_searches')->where('id', $search->id)->value('filters'));
    }

    public function test_itineraries_and_steps_are_returned_in_travel_order(): void
    {
        $search = TripSearch::factory()->create();
        $fastest = Itinerary::factory()->for($search)->optimizedFor(RouteCriterion::Fastest)->create();
        $cheapest = Itinerary::factory()->for($search)->optimizedFor(RouteCriterion::Cheapest)->create();
        foreach ([3, 1, 2] as $sequence) {
            ItineraryStep::factory()->for($fastest)->create(['sequence' => $sequence]);
        }

        $this->assertSame([$fastest->id, $cheapest->id], $search->itineraries->modelKeys());
        $this->assertSame([1, 2, 3], $fastest->steps->pluck('sequence')->all());
        $this->assertTrue($fastest->tripSearch->is($search));
        $step = $fastest->steps->first();
        $this->assertTrue($step->itinerary->is($fastest));
        $this->assertInstanceOf(Connection::class, $step->connection);
        $this->assertSame($step->connection_id, $step->connection->id);
        $this->assertCount(0, $cheapest->steps);
    }

    public function test_factories_fill_a_whole_search_without_breaking_unique_constraints(): void
    {
        $search = TripSearch::factory()->create();
        Itinerary::factory()->count(count(RouteCriterion::cases()))->for($search)->create();
        $itinerary = $search->itineraries->first();
        ItineraryStep::factory()->count(3)->for($itinerary)->create();
        ItineraryStep::factory()->for($itinerary)->create();
        $otherSearch = TripSearch::factory()->create();
        Itinerary::factory()->for($otherSearch)->create();
        Itinerary::factory()->for($otherSearch)->create();

        $this->assertSame(RouteCriterion::cases(), $search->itineraries->pluck('criterion')->all());
        $this->assertSame([1, 2, 3, 4], $itinerary->steps->pluck('sequence')->all());
        $this->assertSame([RouteCriterion::Fastest, RouteCriterion::Cheapest], $otherSearch->itineraries->pluck('criterion')->all());
    }

    public function test_itinerary_criterion_and_preferred_route_are_cast_to_the_criterion_enum(): void
    {
        $itinerary = Itinerary::factory()->optimizedFor(RouteCriterion::Transfers)->create()->fresh();
        $user = User::factory()->create();
        $user->forceFill(['preferred_route' => 'sustainable'])->save();

        $this->assertSame(RouteCriterion::Transfers, $itinerary->criterion);
        $this->assertSame('transfers', DB::table('itineraries')->where('id', $itinerary->id)->value('criterion'));
        $this->assertSame(RouteCriterion::Sustainable, $user->fresh()->preferred_route);
        $this->assertSame('sustainable', DB::table('users')->where('id', $user->id)->value('preferred_route'));
    }
}
