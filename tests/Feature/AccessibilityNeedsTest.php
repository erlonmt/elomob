<?php

namespace Tests\Feature;

use App\Services\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Runs on the demonstration network, whose barriers are deliberate: Estação Recife has steps at the entrance and
 * stairs to the Linha Centro platform, the crossing from it to Recife Antigo has no audible signal, lines 042 and
 * 081 have no visual signs, line 033 has no lift, the walk from Joana Bezerra to Afogados is not wheelchair
 * accessible and the Cais de Santa Rita terminal has no tactile paving.
 */
class AccessibilityNeedsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * Line 042 to Joana Bezerra, then the Linha Centro to Estação Recife and a walk to Recife Antigo.
     *
     * @var list<string>
     */
    private const array BY_LINHA_CENTRO = ['042:Estação Joana Bezerra', 'CENTRO:Estação Recife', 'walk:Recife Antigo'];

    /**
     * The same trip on the Linha Sul, whose platform at Estação Recife has a lift.
     *
     * @var list<string>
     */
    private const array BY_LINHA_SUL = ['042:Estação Joana Bezerra', 'SUL:Estação Recife', 'walk:Recife Antigo'];

    /**
     * Buses only: line 042, a walk to Afogados, line 033 to Derby and line 081 to Recife Antigo.
     *
     * @var list<string>
     */
    private const array BY_BUS = ['042:Estação Joana Bezerra', 'walk:Afogados', '033:Derby', '081:Boa Vista', '081:Recife Antigo'];

    /**
     * The same buses to Derby, then the BRT to Boa Vista and a walk to Recife Antigo. At 15:24 the BRT is leaving
     * Derby, while line 081 only passes at 15:32; both trips reach Recife Antigo at 15:50.
     *
     * @var list<string>
     */
    private const array BY_BUS_AND_BRT = ['042:Estação Joana Bezerra', 'walk:Afogados', '033:Derby', 'BRT:Boa Vista', 'walk:Recife Antigo'];

    /**
     * Line 061 along the Via Mangue to Cais de Santa Rita, then a walk to Recife Antigo.
     *
     * @var list<string>
     */
    private const array BY_VIA_MANGUE = ['061:Pina', '061:Cais de Santa Rita', 'walk:Recife Antigo'];

    /**
     * Fastest trip from Boa Viagem to Recife Antigo by bus and metro at 14:30 for each need.
     *
     * @return array<string, array{list<string>, list<string>}>
     */
    public static function demonstrationRoutes(): array
    {
        return [
            'no need' => [[], self::BY_LINHA_CENTRO],
            'reduced mobility' => [['reduced_mobility'], self::BY_LINHA_SUL],
            'visual impairment' => [['visual'], self::BY_BUS_AND_BRT],
            'wheelchair' => [['wheelchair'], self::BY_VIA_MANGUE],
            'hearing impairment' => [['hearing'], self::BY_VIA_MANGUE],
            'wheelchair and hearing impairment' => [['wheelchair', 'hearing'], self::BY_VIA_MANGUE],
        ];
    }

    /**
     * @param  list<string>  $needs
     * @param  list<string>  $route
     */
    #[DataProvider('demonstrationRoutes')]
    public function test_each_need_changes_the_demonstration_route(array $needs, array $route): void
    {
        $this->assertSame($route, $this->fastestRoute('Boa Viagem', 'Recife Antigo', $needs));
    }

    public function test_hearing_takes_the_brt_where_line_081_has_no_signs(): void
    {
        $this->assertSame(['081:Derby', '081:Boa Vista', '081:Recife Antigo'], $this->fastestRoute('Cordeiro', 'Recife Antigo', [], ['bus'], '14:31'));
        $this->assertSame(['BRT:Derby', 'BRT:Boa Vista', 'walk:Recife Antigo'], $this->fastestRoute('Cordeiro', 'Recife Antigo', ['hearing'], ['bus'], '14:31'));
    }

    /**
     * A need, a change to the demonstration network, and the fastest trip from Boa Viagem to Recife Antigo after it
     * (empty when there is none). A change is ['stop', name, columns], ['line', code, columns] or
     * ['walk', from, to, columns] (both directions).
     *
     * @return array<string, array{list<string>, array<int, mixed>, list<string>}>
     */
    public static function features(): array
    {
        return [
            'wheelchair users need step-free stops' => [['wheelchair'], ['stop', 'Cais de Santa Rita', ['accessible' => false]], []],
            'wheelchair users need adapted vehicles' => [['wheelchair'], ['line', '061', ['adapted' => false]], []],
            'wheelchair users need accessible stretches' => [['wheelchair'], ['walk', 'Cais de Santa Rita', 'Recife Antigo', ['accessible' => false]], []],
            'reduced mobility avoids stairs' => [['reduced_mobility'], ['line', 'SUL', ['has_stairs' => true]], self::BY_VIA_MANGUE],
            'reduced mobility copes with ordinary vehicles' => [['reduced_mobility'], ['line', 'SUL', ['adapted' => false, 'accessible' => false]], self::BY_LINHA_SUL],
            'visual impairment needs audible signals' => [['visual'], ['walk', 'Estação Recife', 'Recife Antigo', ['audible_signals' => true]], self::BY_LINHA_CENTRO],
            'visual impairment needs tactile paving' => [['visual'], ['stop', 'Cais de Santa Rita', ['tactile_paving' => true]], self::BY_VIA_MANGUE],
            'visual impairment needs no tactile paving at a stop passed on board' => [['visual'], ['stop', 'Boa Vista', ['tactile_paving' => false]], self::BY_BUS],
            'visual impairment needs tactile paving where it changes lines' => [['visual'], ['stop', 'Derby', ['tactile_paving' => false]], []],
            'visual impairment needs tactile paving at the destination' => [['visual'], ['stop', 'Recife Antigo', ['tactile_paving' => false]], []],
            'hearing impairment needs visual signs on board' => [['hearing'], ['line', '042', ['visual_signs' => true]], self::BY_LINHA_SUL],
            'hearing impairment needs visual signs at the stops' => [['hearing'], ['stop', 'Cais de Santa Rita', ['visual_signs' => false]], []],
        ];
    }

    /**
     * @param  list<string>  $needs
     * @param  array<int, mixed>  $change
     * @param  list<string>  $route
     */
    #[DataProvider('features')]
    public function test_each_need_depends_on_its_own_features(array $needs, array $change, array $route): void
    {
        $this->change($change);

        $this->assertSame(self::BY_LINHA_CENTRO, $this->fastestRoute('Boa Viagem', 'Recife Antigo', []));
        $this->assertSame($route, $this->fastestRoute('Boa Viagem', 'Recife Antigo', $needs));
    }

    public function test_needs_add_up(): void
    {
        $this->assertSame([], $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['wheelchair', 'visual']));
        $this->assertSame([], $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['visual', 'hearing']));
    }

    /**
     * Both needs take the Via Mangue alone, so their pair only shows it applies both once the route loses what
     * one of them requires.
     */
    public function test_wheelchair_and_hearing_needs_add_up(): void
    {
        $this->change(['line', '061', ['adapted' => false]]);
        $this->assertSame(self::BY_VIA_MANGUE, $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['hearing']));
        $this->assertSame([], $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['wheelchair', 'hearing']));

        $this->change(['line', '061', ['adapted' => true, 'visual_signs' => false]]);
        $this->assertSame(self::BY_VIA_MANGUE, $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['wheelchair']));
        $this->assertSame([], $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['wheelchair', 'hearing']));
    }

    public function test_inaccessible_integrations_block_wheelchair_and_reduced_mobility_only(): void
    {
        $this->change(['line', 'CENTRO', ['accessible' => true, 'has_stairs' => false, 'visual_signs' => true]]);
        DB::table('stops')->where('name', 'Estação Recife')->update(['accessible' => true]);
        $this->assertSame(self::BY_LINHA_CENTRO, $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['reduced_mobility']));

        DB::table('integrations')->where('stop_id', $this->stop('Estação Joana Bezerra'))->update(['accessible' => false]);

        $this->assertSame(self::BY_VIA_MANGUE, $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['reduced_mobility']));
        $this->assertSame(self::BY_VIA_MANGUE, $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['wheelchair']));
        $this->change(['line', '042', ['visual_signs' => true]]);
        $this->assertSame(self::BY_LINHA_CENTRO, $this->fastestRoute('Boa Viagem', 'Recife Antigo', ['hearing']));
    }

    public function test_a_stop_without_the_feature_at_the_origin_leaves_no_route(): void
    {
        $this->assertNotSame([], $this->fastestRoute('Casa Amarela', 'Recife Antigo', [], ['bus']));
        $this->assertSame([], $this->fastestRoute('Casa Amarela', 'Recife Antigo', ['visual'], ['bus']));
    }

    public function test_a_stop_walked_past_needs_the_features(): void
    {
        $walk = ['walk:Recife Antigo', 'walk:Boa Vista'];
        $this->assertSame($walk, $this->fastestRoute('Cais de Santa Rita', 'Boa Vista', ['hearing'], []));

        DB::table('stops')->where('name', 'Recife Antigo')->update(['visual_signs' => false]);

        $this->assertSame($walk, $this->fastestRoute('Cais de Santa Rita', 'Boa Vista', [], []));
        $this->assertSame([], $this->fastestRoute('Cais de Santa Rita', 'Boa Vista', ['hearing'], []));
    }

    public function test_reseeding_restores_accessibility_features_in_place(): void
    {
        $id = $this->stop('Cais de Santa Rita');
        DB::table('stops')->where('id', $id)->update(['tactile_paving' => true, 'visual_signs' => false]);
        $this->change(['line', '042', ['visual_signs' => true]]);

        $this->seed();

        $this->assertSame($id, $this->stop('Cais de Santa Rita'));
        $this->assertDatabaseHas('stops', ['id' => $id, 'accessible' => true, 'tactile_paving' => false, 'visual_signs' => true]);
        $this->assertSame(0, DB::table('connections')->where('line_id', DB::table('lines')->where('code', '042')->value('id'))->where('visual_signs', true)->count());
    }

    private function stop(string $name): int
    {
        return (int) DB::table('stops')->where('name', $name)->value('id');
    }

    /**
     * @param  array<int, mixed>  $change
     */
    private function change(array $change): void
    {
        match ($change[0]) {
            'stop' => DB::table('stops')->where('name', $change[1])->update($change[2]),
            'line' => DB::table('connections')->where('line_id', DB::table('lines')->where('code', $change[1])->value('id'))->update($change[2]),
            'walk' => DB::table('connections')->whereNull('line_id')
                ->where('transport_mode_id', DB::table('transport_modes')->where('code', 'walk')->value('id'))
                ->whereIn('from_stop_id', [$this->stop($change[1]), $this->stop($change[2])])
                ->whereIn('to_stop_id', [$this->stop($change[1]), $this->stop($change[2])])
                ->update($change[3]),
            default => throw new InvalidArgumentException('Unknown change to the network.'),
        };
    }

    /**
     * The fastest trip as "line code or mode code:stop reached" for each step, or an empty list when there is none.
     *
     * @param  list<string>  $needs
     * @param  list<string>  $modes
     * @return list<string>
     */
    private function fastestRoute(string $origin, string $destination, array $needs, array $modes = ['bus', 'metro'], string $time = '14:30'): array
    {
        $plans = app(RoutePlanner::class)->plan($this->stop($origin), $this->stop($destination), [
            'modes' => $modes, 'departure_at' => "2026-10-30T{$time}:00-03:00", 'accessibility_needs' => $needs,
        ]);
        if (! isset($plans['fastest'])) {
            return [];
        }
        $steps = DB::table('connections')
            ->join('stops', 'stops.id', '=', 'connections.to_stop_id')
            ->join('transport_modes', 'transport_modes.id', '=', 'connections.transport_mode_id')
            ->leftJoin('lines', 'lines.id', '=', 'connections.line_id')
            ->whereIn('connections.id', array_column($plans['fastest']['steps'], 'connection_id'))
            ->selectRaw("connections.id, COALESCE(lines.code, transport_modes.code) || ':' || stops.name AS step")
            ->pluck('step', 'id');

        return array_map(fn (array $step): string => $steps[$step['connection_id']], $plans['fastest']['steps']);
    }
}
