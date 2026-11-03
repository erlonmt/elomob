<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobilityApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-30 14:30:00', 'America/Recife'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function stop(string $name): int
    {
        return (int) DB::table('stops')->where('name', $name)->value('id');
    }

    /** @return array{origin_id: int, destination_id: int, modes: array<int, string>, departure_at: string} */
    private function searchData(): array
    {
        return [
            'origin_id' => $this->stop('Boa Viagem'),
            'destination_id' => $this->stop('Recife Antigo'),
            'modes' => ['bus', 'metro', 'bike', 'taxi', 'app'],
            'departure_at' => '2026-10-30T14:30:00-03:00',
        ];
    }

    public function test_session_registration_login_and_logout_use_json_and_never_expose_passwords(): void
    {
        $this->getJson('/api/v1/sessao')->assertOk()->assertJsonPath('data.user', null)->assertJsonStructure(['data' => ['csrf_token']]);
        $this->postJson('/api/v1/cadastrar', [
            'name' => 'Mariana Costa',
            'email' => 'mariana@example.test',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])->assertCreated()->assertJsonPath('data.user.name', 'Mariana Costa')
            ->assertJsonPath('data.user.preferred_route', 'fastest')
            ->assertJsonPath('data.user.accessibility_needs', [])->assertJsonMissingPath('data.user.password');
        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('secure-pass-123', User::first()->password));
        $this->getJson('/api/v1/perfil')->assertOk()->assertJsonPath('data.email', 'mariana@example.test')->assertJsonMissingPath('data.password');
        $this->postJson('/api/v1/sair')->assertOk()->assertJsonPath('data.user', null);
        $this->assertGuest();
        $this->postJson('/api/v1/entrar', ['email' => 'mariana@example.test', 'password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/v1/entrar', ['email' => 'mariana@example.test', 'password' => 'secure-pass-123'])
            ->assertOk()->assertJsonPath('data.user.email', 'mariana@example.test');
    }

    public function test_private_endpoints_return_json_unauthorized_to_guests(): void
    {
        foreach (['/api/v1/perfil', '/api/v1/favoritos', '/api/v1/historico'] as $path) {
            $this->getJson($path)->assertUnauthorized();
        }
        $this->putJson('/api/v1/perfil', [])->assertUnauthorized();
        $this->postJson('/api/v1/favoritos', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/favoritos/1')->assertUnauthorized();
    }

    public function test_catalog_and_existing_endpoints_return_demonstration_data_and_apply_filters(): void
    {
        $this->getJson('/api/v1/catalogo')->assertOk()->assertJsonPath('source', 'demo')
            ->assertJsonCount(17, 'data.stops')->assertJsonCount(5, 'data.modes')->assertJsonCount(7, 'data.lines')
            ->assertJsonCount(6, 'data.integrations');
        $this->getJson('/api/v1/paradas?q=boa%20viagem')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Boa Viagem');
        $this->getJson('/api/v1/linhas?q=042')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', '042');
        $this->getJson('/api/v1/linhas?q=042&mode=metro')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/paradas?latitude=100&longitude=-34')->assertUnprocessable()->assertJsonValidationErrors('latitude');
        $this->getJson('/api/v1/paradas?latitude=-8')->assertUnprocessable()->assertJsonValidationErrors('longitude');
    }

    public function test_stop_proximity_endpoint_returns_only_stops_inside_the_requested_radius(): void
    {
        $this->getJson('/api/v1/paradas?latitude=-8.119&longitude=-34.899&radius=100')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Boa Viagem');
        $this->getJson('/api/v1/paradas?latitude=0&longitude=0&radius=100')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/paradas?lat=-8.119&lng=-34.899&radius_meters=100')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Boa Viagem');
    }

    public function test_cached_catalog_uses_plain_arrays_and_remains_usable_after_a_cache_round_trip(): void
    {
        $cold = $this->getJson('/api/v1/catalogo')->assertOk()->assertJsonCount(17, 'data.stops');
        $cached = Cache::get('mobility:catalog:v5');
        $this->assertIsArray($cached);
        $this->assertSame($cached, json_decode(json_encode($cached, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR));
        Cache::put('mobility:catalog:v5', unserialize(serialize($cached), ['allowed_classes' => false]), 30);

        $warm = $this->getJson('/api/v1/catalogo')->assertOk()->assertJsonCount(17, 'data.stops')
            ->assertJsonCount(5, 'data.modes')->assertJsonCount(7, 'data.lines')->assertJsonCount(6, 'data.integrations');
        $this->assertSame($cold->json(), $warm->json());
        $this->getJson('/api/v1/paradas?q=boa%20viagem')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/linhas?q=042')->assertOk()->assertJsonPath('data.0.code', '042');
    }

    public function test_catalogs_cached_by_earlier_versions_are_not_served(): void
    {
        foreach (['mobility:catalog:v2', 'mobility:catalog:v3'] as $earlierKey) {
            Cache::put($earlierKey, ['stops' => [], 'modes' => [], 'lines' => [], 'integrations' => []], 30);
        }

        $this->getJson('/api/v1/catalogo')->assertOk()->assertJsonCount(17, 'data.stops')->assertJsonCount(6, 'data.integrations');
    }

    public function test_catalog_integrations_say_whether_they_integrate_the_fare(): void
    {
        $integrations = $this->getJson('/api/v1/catalogo')->assertOk()->collect('data.integrations')
            ->mapWithKeys(fn (array $integration): array => [$integration['name'].': '.$integration['from_mode'].' → '.$integration['to_mode'] => $integration['fare_integrated']]);

        $this->assertSame([
            'Estação Joana Bezerra: bus → metro' => true,
            'Estação Recife: metro → bike' => false,
            'Terminal Integrado CDU: bus → bike' => false,
            'Estação Joana Bezerra: metro → bus' => true,
            'Estação Recife: bike → metro' => false,
            'Terminal Integrado CDU: bike → bus' => false,
        ], $integrations->all());
    }

    public function test_catalog_stops_expose_tactile_paving_and_visual_signs(): void
    {
        $stops = $this->getJson('/api/v1/catalogo')->assertOk()->collect('data.stops')->keyBy('name');

        $this->assertSame(['accessible' => true, 'tactile_paving' => false, 'visual_signs' => true], Arr::only($stops['Cais de Santa Rita'], ['accessible', 'tactile_paving', 'visual_signs']));
        $this->assertSame(['accessible' => false, 'tactile_paving' => true, 'visual_signs' => true], Arr::only($stops['Estação Recife'], ['accessible', 'tactile_paving', 'visual_signs']));
        $this->getJson('/api/v1/paradas?q=cais')->assertOk()->assertJsonPath('data.0.tactile_paving', false);
    }

    public function test_steps_say_which_needs_they_meet(): void
    {
        $fastest = $this->postJson('/api/v1/buscas', array_replace($this->searchData(), ['modes' => ['bus', 'metro']]))->assertCreated()
            ->collect('data.itineraries')->firstWhere('criterion', 'fastest');
        $steps = $this->getJson('/api/v1/trajetos/'.$fastest['id'])->assertOk()->json('data.steps');

        $this->assertSame(['042', 'CENTRO', null], array_column($steps, 'line_code'));
        $this->assertSame([false, true, false], array_column($steps, 'has_stairs'));
        $this->assertSame([
            ['wheelchair', 'reduced_mobility', 'visual'],
            ['visual'],
            ['reduced_mobility', 'hearing'],
        ], array_column($steps, 'accessible_for'));
        $this->assertSame(array_column($steps, 'accessible_for'), array_column($fastest['steps'], 'accessible_for'));
    }

    public function test_stops_passed_on_board_take_no_need_away_from_a_step(): void
    {
        DB::table('stops')->whereIn('name', ['Derby', 'Boa Vista'])->update(['tactile_paving' => false]);
        DB::table('stops')->where('name', 'Recife Antigo')->update(['accessible' => false]);

        $steps = $this->postJson('/api/v1/buscas', [
            'origin_id' => $this->stop('Cordeiro'), 'destination_id' => $this->stop('Recife Antigo'), 'modes' => ['bus'], 'departure_at' => '2026-10-30T14:31:00-03:00',
        ])->assertCreated()->collect('data.itineraries')->firstWhere('criterion', 'fastest')['steps'];

        $this->assertSame(['Derby', 'Boa Vista', 'Recife Antigo'], array_column($steps, 'destination'));
        $this->assertSame(['081', '081', '081'], array_column($steps, 'line_code'));
        $this->assertSame([
            ['wheelchair', 'reduced_mobility', 'visual'],
            ['wheelchair', 'reduced_mobility', 'visual'],
            ['reduced_mobility', 'visual'],
        ], array_column($steps, 'accessible_for'));
    }

    public function test_a_need_the_origin_lacks_leaves_the_search_without_itineraries(): void
    {
        $search = ['origin_id' => $this->stop('Casa Amarela'), 'destination_id' => $this->stop('Recife Antigo'), 'modes' => ['bus'], 'departure_at' => '2026-10-30T14:30:00-03:00'];
        $this->assertNotSame([], $this->postJson('/api/v1/buscas', $search)->assertCreated()->json('data.itineraries'));

        $this->actingAs(User::factory()->create(['accessibility_needs' => ['visual']]))
            ->postJson('/api/v1/buscas', $search)->assertCreated()
            ->assertJsonPath('data.filters.accessibility_needs', ['visual'])
            ->assertJsonPath('data.itineraries', []);
    }

    public function test_search_returns_persisted_totals_steps_and_the_actual_itinerary_geometry(): void
    {
        $response = $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated()
            ->assertJsonPath('source', 'demo')
            ->assertJsonPath('data.origin', 'Boa Viagem')->assertJsonPath('data.destination', 'Recife Antigo')
            ->assertJsonCount(5, 'data.itineraries')->assertJsonMissingPath('data.session_hash');
        $search = $response->json('data');
        $this->getJson('/api/v1/buscas/'.$search['id'])->assertOk()->assertJsonPath('data.filters.modes', $this->searchData()['modes']);
        foreach ($search['itineraries'] as $itinerary) {
            $this->assertSame($itinerary['total_minutes'], array_sum(array_column($itinerary['steps'], 'minutes')));
            $this->assertSame($itinerary['total_cost_cents'], array_sum(array_column($itinerary['steps'], 'cost_cents')));
            $this->assertSame($itinerary['total_distance_meters'], array_sum(array_column($itinerary['steps'], 'distance_meters')));
            $detail = $this->getJson('/api/v1/trajetos/'.$itinerary['id'])->assertOk()
                ->assertJsonPath('data.search.id', $search['id'])->assertJsonPath('data.search.origin', 'Boa Viagem')
                ->assertJsonPath('data.search.destination', 'Recife Antigo')
                ->assertJsonPath('data.geojson.type', 'FeatureCollection')->assertJsonMissingPath('data.geometry');
            $this->assertCount(count($itinerary['steps']), $detail->json('data.geojson.features'));
            $detail->assertJsonPath('data.geojson.features.0.geometry.type', 'LineString');
        }
    }

    public function test_private_responses_are_never_stored_while_the_catalog_may_be(): void
    {
        $catalogCache = $this->getJson('/api/v1/catalogo')->assertOk()->headers->get('Cache-Control');
        $this->assertStringNotContainsString('no-store', $catalogCache);

        $search = $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->postJson('/api/v1/cadastrar', [
            'name' => 'Ana Lima', 'email' => 'ana@example.test', 'password' => 'secure-pass-123', 'password_confirmation' => 'secure-pass-123',
        ])->assertCreated()->assertHeader('Cache-Control', 'no-store, private');

        foreach (['/sessao', '/buscas/'.$search['id'], '/trajetos/'.$search['itineraries'][0]['id'], '/perfil', '/favoritos', '/historico'] as $path) {
            $this->getJson('/api/v1'.$path)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        }
    }

    public function test_search_records_and_returns_the_integration_time_of_each_step(): void
    {
        $data = array_replace($this->searchData(), ['modes' => ['bus', 'metro'], 'departure_at' => '2026-10-30T14:30:00-03:00']);
        $itinerary = $this->postJson('/api/v1/buscas', $data)->assertCreated()->collect('data.itineraries')->firstWhere('criterion', 'fastest');

        $this->assertSame([0, 3, 0], array_column($itinerary['steps'], 'transfer_minutes'));
        $this->assertDatabaseHas('itinerary_steps', ['itinerary_id' => $itinerary['id'], 'sequence' => 2, 'transfer_minutes' => 3, 'wait_minutes' => 3]);
        $this->getJson('/api/v1/trajetos/'.$itinerary['id'])->assertOk()
            ->assertJsonPath('data.steps.1.transfer_minutes', 3)
            ->assertJsonPath('data.geojson.features.1.properties.transfer_minutes', 3);
    }

    public function test_search_records_and_returns_the_fare_integration_of_each_step(): void
    {
        $data = array_replace($this->searchData(), ['modes' => ['bus', 'metro']]);
        $itinerary = $this->postJson('/api/v1/buscas', $data)->assertCreated()->collect('data.itineraries')->firstWhere('criterion', 'fastest');

        $this->assertSame([380, 0, 0], array_column($itinerary['steps'], 'cost_cents'));
        $this->assertSame([false, true, false], array_column($itinerary['steps'], 'fare_integrated'));
        $this->assertSame(380, $itinerary['total_cost_cents']);
        $this->assertDatabaseHas('itinerary_steps', ['itinerary_id' => $itinerary['id'], 'sequence' => 2, 'cost_cents' => 0, 'fare_integrated' => true]);
        $this->getJson('/api/v1/trajetos/'.$itinerary['id'])->assertOk()->assertJsonPath('data.steps.1.fare_integrated', true);
    }

    public function test_the_itinerary_detail_says_when_a_search_to_leave_now_was_made(): void
    {
        $data = Arr::except(array_replace($this->searchData(), ['modes' => ['bus', 'metro']]), 'departure_at');
        $search = $this->postJson('/api/v1/buscas', $data)->assertCreated()->json('data');

        $this->getJson('/api/v1/trajetos/'.$search['itineraries'][0]['id'])->assertOk()
            ->assertJsonPath('data.search.departure_at', null)
            ->assertJsonPath('data.search.created_at', '2026-10-30T17:30:00+00:00');
    }

    public function test_the_requested_departure_has_its_own_column_and_filters_keep_only_the_options(): void
    {
        $search = $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated()
            ->assertJsonPath('data.departure_at', '2026-10-30T14:30:00-03:00')->json('data');

        $this->assertSame(['modes' => ['bus', 'metro', 'bike', 'taxi', 'app'], 'accessibility_needs' => []], $search['filters']);
        $this->assertDatabaseHas('trip_searches', ['id' => $search['id'], 'departure_at' => '2026-10-30 17:30:00']);
        $this->getJson('/api/v1/trajetos/'.$search['itineraries'][0]['id'])->assertOk()
            ->assertJsonPath('data.search.departure_at', '2026-10-30T14:30:00-03:00');
        $this->postJson('/api/v1/buscas', Arr::except($this->searchData(), ['departure_at']))->assertCreated()->assertJsonPath('data.departure_at', null);
    }

    public function test_search_validation_rejects_the_same_stop_even_with_mixed_id_types(): void
    {
        $data = $this->searchData();
        $data['destination_id'] = (string) $data['origin_id'];
        $this->postJson('/api/v1/buscas', $data)->assertUnprocessable()->assertJsonValidationErrors('destination_id');
        $this->postJson('/api/v1/buscas', array_replace($this->searchData(), ['modes' => ['plane']]))->assertUnprocessable()->assertJsonValidationErrors('modes.0');
        $this->postJson('/api/v1/buscas', array_replace($this->searchData(), ['departure_at' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('departure_at');
        $this->assertDatabaseCount('trip_searches', 0);
    }

    public function test_a_departure_must_be_from_today_up_to_ninety_days_ahead_in_recife(): void
    {
        $accepted = [
            'start of today' => '2026-10-30T00:00:00-03:00',
            'earlier today, without an offset (Recife time)' => '2026-10-30T01:00',
            'last minute of the 90th day' => '2027-01-28T23:59:00-03:00',
        ];
        foreach ($accepted as $case => $departure) {
            $this->postJson('/api/v1/buscas', array_replace($this->searchData(), ['departure_at' => $departure]))->assertCreated();
        }

        $rejected = [
            'yesterday' => ['2026-10-29T23:59:00-03:00', 'Escolha uma saída a partir de hoje.'],
            'yesterday in Recife, today in UTC' => ['2026-10-30T02:30:00Z', 'Escolha uma saída a partir de hoje.'],
            'long ago' => ['1900-01-01T10:00:00-03:00', 'Escolha uma saída a partir de hoje.'],
            'the 91st day' => ['2027-01-29T00:00:00-03:00', 'Escolha uma saída nos próximos 90 dias.'],
            'far ahead' => ['9999-01-01T10:00:00-03:00', 'Escolha uma saída nos próximos 90 dias.'],
        ];
        foreach ($rejected as $case => [$departure, $message]) {
            $this->postJson('/api/v1/buscas', array_replace($this->searchData(), ['departure_at' => $departure]))->assertUnprocessable()
                ->assertJsonValidationErrors(['departure_at' => $message]);
        }
        $this->assertDatabaseCount('trip_searches', count($accepted));
    }

    public function test_in_the_evening_a_departure_earlier_the_same_day_in_recife_is_still_today(): void
    {
        // 22:00 in Recife is already the next day in UTC.
        $this->travelTo(Carbon::parse('2026-10-30 22:00:00', 'America/Recife'));

        $this->postJson('/api/v1/buscas', array_replace($this->searchData(), ['departure_at' => '2026-10-30T20:00:00-03:00']))->assertCreated();
        $this->postJson('/api/v1/buscas', array_replace($this->searchData(), ['departure_at' => '2026-10-30T20:00']))->assertCreated();
    }

    public function test_guest_searches_are_available_only_to_the_creating_session(): void
    {
        $search = $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated()->json('data');
        $this->getJson('/api/v1/buscas/'.$search['id'])->assertOk();
        $this->flushSession();
        $this->getJson('/api/v1/buscas/'.$search['id'])->assertNotFound();
        $this->getJson('/api/v1/trajetos/'.$search['itineraries'][0]['id'])->assertNotFound();
    }

    public function test_authenticated_searches_and_history_are_visible_only_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $search = $this->actingAs($owner)->postJson('/api/v1/buscas', $this->searchData())->assertCreated()->json('data');
        $this->actingAs(User::factory()->create())->getJson('/api/v1/buscas/'.$search['id'])->assertNotFound();
        $this->getJson('/api/v1/trajetos/'.$search['itineraries'][0]['id'])->assertNotFound();
        $this->getJson('/api/v1/historico')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($owner)->getJson('/api/v1/buscas/'.$search['id'])->assertOk();
        $this->getJson('/api/v1/historico')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $search['id']);
    }

    public function test_switching_accounts_and_logging_out_never_grants_access_to_another_accounts_searches(): void
    {
        $owner = User::factory()->create(['password' => Hash::make('secure-pass-123')]);
        $other = User::factory()->create(['password' => Hash::make('secure-pass-123')]);
        $this->postJson('/api/v1/entrar', ['email' => $owner->email, 'password' => 'secure-pass-123'])->assertOk();
        $search = $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated()->json('data');
        $this->postJson('/api/v1/entrar', ['email' => $other->email, 'password' => 'secure-pass-123'])->assertOk();
        $this->getJson('/api/v1/buscas/'.$search['id'])->assertNotFound();
        $this->getJson('/api/v1/trajetos/'.$search['itineraries'][0]['id'])->assertNotFound();
        $this->postJson('/api/v1/sair')->assertOk();
        $this->getJson('/api/v1/buscas/'.$search['id'])->assertNotFound();
        $this->assertNull(session('mobility_key'));
        $this->postJson('/api/v1/entrar', ['email' => $owner->email, 'password' => 'secure-pass-123'])->assertOk();
        $this->getJson('/api/v1/buscas/'.$search['id'])->assertOk();
    }

    public function test_profile_preferences_can_be_disabled_and_are_applied_to_future_searches(): void
    {
        $user = User::factory()->create();
        $data = ['name' => 'Nome atualizado', 'email' => $user->email, 'preferred_route' => 'sustainable', 'transport_modes' => ['bus', 'bike'], 'accessibility_needs' => ['wheelchair', 'visual']];
        $this->actingAs($user)->putJson('/api/v1/perfil', $data)->assertOk()
            ->assertJsonPath('data.name', 'Nome atualizado')->assertJsonPath('data.accessibility_needs', ['wheelchair', 'visual']);
        $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated()->assertJsonPath('data.filters.accessibility_needs', ['wheelchair', 'visual']);
        $this->putJson('/api/v1/perfil', Arr::except($data, ['accessibility_needs']))->assertOk()->assertJsonPath('data.accessibility_needs', []);
        $this->assertSame([], $user->fresh()->accessibility_needs);
        $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated()->assertJsonPath('data.filters.accessibility_needs', []);
    }

    public function test_profile_rejects_unknown_or_repeated_accessibility_needs(): void
    {
        $user = User::factory()->create();
        $data = ['name' => $user->name, 'email' => $user->email, 'preferred_route' => 'fastest', 'transport_modes' => ['bus']];

        $this->actingAs($user)->putJson('/api/v1/perfil', $data + ['accessibility_needs' => ['visual', 'flying']])->assertUnprocessable()
            ->assertJsonValidationErrors(['accessibility_needs.1' => 'O valor escolhido para tipo de necessidade é inválido.']);
        $this->putJson('/api/v1/perfil', $data + ['accessibility_needs' => ['hearing', 'hearing']])->assertUnprocessable()
            ->assertJsonValidationErrors(['accessibility_needs.0' => 'O campo tipo de necessidade tem um valor repetido.']);
        $this->putJson('/api/v1/perfil', $data + ['accessibility_needs' => 'visual'])->assertUnprocessable()
            ->assertJsonValidationErrors(['accessibility_needs' => 'O campo tipos de necessidade deve ser uma lista.']);
        $this->assertSame([], $user->fresh()->accessibility_needs);
        $this->putJson('/api/v1/perfil', array_replace($data, ['preferred_route' => 'public_transport', 'accessibility_needs' => []]))->assertUnprocessable()
            ->assertJsonValidationErrors('preferred_route');
    }

    public function test_profile_lists_reject_objects_with_a_portuguese_message(): void
    {
        $user = User::factory()->create();
        $data = ['name' => $user->name, 'email' => $user->email, 'preferred_route' => 'fastest', 'transport_modes' => ['bus']];

        $this->actingAs($user)->putJson('/api/v1/perfil', $data + ['accessibility_needs' => ['a' => 'visual']])->assertUnprocessable()
            ->assertJsonValidationErrors(['accessibility_needs' => 'O campo tipos de necessidade deve ser uma lista.']);
        $this->putJson('/api/v1/perfil', array_replace($data, ['transport_modes' => ['a' => 'bus']]))->assertUnprocessable()
            ->assertJsonValidationErrors(['transport_modes' => 'O campo modos de transporte deve ser uma lista.']);
        $this->getJson('/api/v1/perfil')->assertOk()->assertJsonPath('data.accessibility_needs', [])
            ->assertJsonPath('data.transport_modes', ['bus', 'metro', 'bike', 'taxi', 'app']);
    }

    public function test_profile_accepts_every_route_criterion_and_rejects_unknown_values_or_walking(): void
    {
        $user = User::factory()->create();
        $data = ['name' => $user->name, 'email' => $user->email, 'preferred_route' => 'transfers', 'transport_modes' => ['bus']];
        foreach (['fastest', 'cheapest', 'sustainable', 'transfers'] as $criterion) {
            $this->actingAs($user)->putJson('/api/v1/perfil', array_replace($data, ['preferred_route' => $criterion]))
                ->assertOk()->assertJsonPath('data.preferred_route', $criterion);
        }
        $this->getJson('/api/v1/perfil')->assertOk()->assertJsonPath('data.preferred_route', 'transfers');

        $this->putJson('/api/v1/perfil', array_replace($data, ['preferred_route' => 'shortest']))->assertUnprocessable()
            ->assertJsonValidationErrors(['preferred_route' => 'O valor escolhido para tipo de rota preferida é inválido.']);
        $this->putJson('/api/v1/perfil', array_replace($data, ['transport_modes' => ['bus', 'walk']]))->assertUnprocessable()
            ->assertJsonValidationErrors(['transport_modes.1' => 'O valor escolhido para modo de transporte é inválido.']);
        $this->assertSame('transfers', $user->fresh()->preferred_route->value);
    }

    public function test_walking_is_not_a_search_mode_but_is_a_catalog_mode_filter(): void
    {
        $this->postJson('/api/v1/buscas', array_replace($this->searchData(), ['modes' => ['bus', 'walk']]))->assertUnprocessable()
            ->assertJsonValidationErrors(['modes.1' => 'O valor escolhido para transporte é inválido.']);
        $this->assertDatabaseCount('trip_searches', 0);

        $this->getJson('/api/v1/paradas?mode=walk')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/linhas?mode=walk')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/paradas?mode=plane')->assertUnprocessable()
            ->assertJsonValidationErrors(['mode' => 'O valor escolhido para modo de transporte é inválido.']);
        $this->getJson('/api/v1/catalogo')->assertOk()->assertJsonPath('data.modes.*.code', ['bus', 'metro', 'bike', 'taxi', 'app']);
    }

    public function test_search_and_itinerary_responses_return_criteria_as_plain_strings(): void
    {
        $search = $this->postJson('/api/v1/buscas', $this->searchData())->assertCreated()
            ->assertJsonPath('data.itineraries.*.criterion', ['fastest', 'cheapest', 'transfers', 'sustainable', 'public_transport'])->json('data');

        $this->getJson('/api/v1/buscas/'.$search['id'])->assertOk()
            ->assertJsonPath('data.itineraries.*.criterion', ['fastest', 'cheapest', 'transfers', 'sustainable', 'public_transport']);
        $this->getJson('/api/v1/trajetos/'.$search['itineraries'][2]['id'])->assertOk()->assertJsonPath('data.criterion', 'transfers');
    }

    public function test_favorites_enforce_ownership_and_unique_labels_per_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $favorite = $this->actingAs($owner)->postJson('/api/v1/favoritos', ['label' => 'Casa', 'stop_id' => $this->stop('Graças')])
            ->assertCreated()->assertJsonPath('data.name', 'Graças')->json('data.id');
        $this->postJson('/api/v1/favoritos', ['label' => 'Casa', 'stop_id' => $this->stop('Derby')])->assertUnprocessable()->assertJsonValidationErrors('label');
        $this->getJson('/api/v1/favoritos')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($other)->getJson('/api/v1/favoritos')->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson('/api/v1/favoritos/'.$favorite)->assertNotFound();
        $this->postJson('/api/v1/favoritos', ['label' => 'Casa', 'stop_id' => $this->stop('Derby')])->assertCreated();
        $this->actingAs($owner)->deleteJson('/api/v1/favoritos/'.$favorite)->assertOk()->assertJsonPath('data.deleted', true);
        $this->getJson('/api/v1/favoritos')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_validation_messages_reach_people_in_portuguese(): void
    {
        $account = ['name' => 'Mariana', 'email' => 'mariana@example.test', 'password' => 'secure-pass-123', 'password_confirmation' => 'secure-pass-123'];
        $this->postJson('/api/v1/cadastrar', array_replace($account, ['password_confirmation' => 'other-pass-123']))->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'A confirmação de senha não confere.']);
        $this->postJson('/api/v1/cadastrar', array_replace($account, ['name' => '', 'password' => 'short', 'password_confirmation' => 'short']))->assertUnprocessable()
            ->assertJsonPath('message', 'O campo nome é obrigatório. (e mais 1 erro)')
            ->assertJsonValidationErrors(['password' => 'O campo senha deve ter pelo menos 8 caracteres.']);
        $this->postJson('/api/v1/cadastrar', $account)->assertCreated();
        $this->postJson('/api/v1/sair')->assertOk();
        $this->postJson('/api/v1/cadastrar', $account)->assertUnprocessable()->assertJsonValidationErrors(['email' => 'Este e-mail já está em uso.']);

        $this->actingAs(User::sole());
        $this->postJson('/api/v1/favoritos', ['label' => 'Casa', 'stop_id' => $this->stop('Graças')])->assertCreated();
        $this->postJson('/api/v1/favoritos', ['label' => 'Casa', 'stop_id' => $this->stop('Derby')])->assertUnprocessable()
            ->assertJsonValidationErrors(['label' => 'Você já tem um local salvo com este nome.']);
        $this->putJson('/api/v1/perfil', ['name' => 'Mariana', 'email' => 'mariana@example.test', 'preferred_route' => 'fastest', 'transport_modes' => []])->assertUnprocessable()
            ->assertJsonValidationErrors(['transport_modes' => 'Selecione ao menos um modo de transporte.']);
    }

    public function test_public_catalog_requests_do_not_consume_the_login_rate_limit(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secure-pass-123')]);
        for ($request = 0; $request < 5; $request++) {
            $this->getJson('/api/v1/paradas')->assertOk();
        }
        $this->postJson('/api/v1/entrar', ['email' => $user->email, 'password' => 'secure-pass-123'])->assertOk();
    }

    public function test_catalog_rate_limits_do_not_block_other_users_behind_the_same_proxy(): void
    {
        $this->actingAs(User::factory()->create());
        for ($request = 0; $request < 60; $request++) {
            $this->getJson('/api/v1/catalogo')->assertOk();
        }
        $this->getJson('/api/v1/catalogo')->assertTooManyRequests();
        $this->actingAs(User::factory()->create())->getJson('/api/v1/catalogo')->assertOk();
    }

    public function test_search_rate_limits_do_not_block_other_users_behind_the_same_proxy(): void
    {
        $this->actingAs(User::factory()->create(['accessibility_needs' => ['reduced_mobility']]));
        $data = array_replace($this->searchData(), ['modes' => ['metro']]);
        for ($request = 0; $request < 30; $request++) {
            $this->postJson('/api/v1/buscas', $data)->assertCreated();
        }
        $this->postJson('/api/v1/buscas', $data)->assertTooManyRequests();
        $this->actingAs(User::factory()->create())->postJson('/api/v1/buscas', $data)->assertCreated();
    }

    public function test_login_limits_apply_to_normalized_accounts_without_blocking_other_accounts(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create(['password' => Hash::make('secure-pass-123')]);
        for ($request = 0; $request < 5; $request++) {
            $this->postJson('/api/v1/entrar', ['email' => mb_strtoupper($owner->email), 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson('/api/v1/entrar', ['email' => $owner->email, 'password' => 'wrong'])->assertTooManyRequests();
        $this->postJson('/api/v1/entrar', ['email' => $other->email, 'password' => 'secure-pass-123'])->assertOk();
        $this->assertAuthenticatedAs($other);
    }
}
