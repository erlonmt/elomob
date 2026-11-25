<?php

namespace Tests\Unit;

use App\Enums\AccessibilityNeed;
use App\Enums\RouteCriterion;
use App\Services\Planning\Network;
use App\Services\Planning\PlanningOptions;
use App\Services\Planning\RoutePlanner;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regras do planejador sobre uma rede pequena, sem banco:
 *
 *   A ──ônibus L1 (10 min, R$ 3,80)──► B ──metrô M1 (5 min, R$ 1,60)──► C
 *   A ─────────────táxi (12 min, R$ 20,00)─────────────────────────────► C
 *   B ──caminhada (20 min, com escada)─────────────────────────────────► C
 *
 * Em B há integração ônibus → metrô com 2 min de troca e tarifa integrada.
 */
class RoutePlannerTest extends TestCase
{
    private const BUS = 1;

    private const METRO = 2;

    private const TAXI = 3;

    private const WALK = 4;

    private const A = 1;

    private const B = 2;

    private const C = 3;

    #[Test]
    public function integrated_fare_charges_only_the_difference(): void
    {
        $route = $this->plan(RouteCriterion::Cheapest);

        $this->assertSame([1, 2], array_column($route->steps, 'connection_id'));
        $this->assertSame(380, $route->totalCostCents);
        $this->assertSame([380, 0], array_column($route->steps, 'cost_cents'));
        $this->assertSame([false, true], array_column($route->steps, 'fare_integrated'));
        $this->assertSame(1, $route->transfers);
    }

    #[Test]
    public function boarding_waits_half_the_frequency_and_transfer_time_is_added(): void
    {
        $route = $this->plan(RouteCriterion::Cheapest);

        // L1: 10 min + espera 5 (frequência 10). M1: 5 min + espera 3 (frequência 6) + troca 2.
        $this->assertSame([5, 3], array_column($route->steps, 'wait_minutes'));
        $this->assertSame([0, 2], array_column($route->steps, 'transfer_minutes'));
        $this->assertSame([15, 10], array_column($route->steps, 'minutes'));
        $this->assertSame(25, $route->totalMinutes);
    }

    #[Test]
    public function fastest_prefers_the_taxi(): void
    {
        $route = $this->plan(RouteCriterion::Fastest);

        $this->assertSame([3], array_column($route->steps, 'connection_id'));
        $this->assertSame(12, $route->totalMinutes);
        $this->assertSame(0, $route->transfers);
    }

    #[Test]
    public function mode_filter_keeps_only_allowed_modes_and_walking(): void
    {
        $route = $this->plan(RouteCriterion::Fastest, modes: [self::BUS]);

        // Sem metrô e sem táxi: ônibus até B e caminhada até C.
        $this->assertSame([1, 4], array_column($route->steps, 'connection_id'));
        $this->assertSame([380, 0], array_column($route->steps, 'cost_cents'));
    }

    #[Test]
    public function lines_are_not_used_after_service_ends(): void
    {
        $route = $this->plan(RouteCriterion::Cheapest, hour: 23);

        $this->assertSame([3], array_column($route->steps, 'connection_id'));
    }

    #[Test]
    public function boarding_before_service_waits_until_it_starts(): void
    {
        $route = $this->plan(RouteCriterion::Cheapest, hour: 4, minute: 50);

        $this->assertSame(10, $route->steps[0]['wait_minutes']);
    }

    #[Test]
    public function wheelchair_avoids_inaccessible_stops_and_stairs(): void
    {
        // O metrô M1 não é adaptado e a caminhada tem escada: só resta o táxi.
        $route = $this->plan(RouteCriterion::Cheapest, needs: [AccessibilityNeed::Wheelchair]);

        $this->assertSame([3], array_column($route->steps, 'connection_id'));
    }

    #[Test]
    public function returns_null_when_destination_does_not_meet_the_needs(): void
    {
        // C não tem piso tátil.
        $route = $this->plan(RouteCriterion::Fastest, needs: [AccessibilityNeed::Visual]);

        $this->assertNull($route);
    }

    private function plan(RouteCriterion $criterion, array $modes = [], array $needs = [], int $hour = 10, int $minute = 0)
    {
        $options = new PlanningOptions(
            departure: CarbonImmutable::create(2026, 11, 20, $hour, $minute, 0, 'America/Recife'),
            allowedModeIds: $modes,
            needs: $needs,
        );

        return (new RoutePlanner)->plan($this->network(), self::A, self::C, $criterion, $options);
    }

    private function network(): Network
    {
        $stop = fn (bool $accessible = true, bool $tactile = true) => [
            'accessible' => $accessible, 'tactile_paving' => $tactile, 'visual_signs' => true,
        ];
        $connection = fn (int $id, int $from, int $to, int $mode, ?int $line, int $minutes, ?int $fare, array $features = []) => [
            'id' => $id, 'from' => $from, 'to' => $to, 'mode' => $mode, 'line' => $line,
            'minutes' => $minutes, 'distance' => $minutes * 400, 'fare' => $fare,
            ...['accessible' => true, 'has_stairs' => false, 'adapted' => true, 'audible_signals' => true, 'visual_signs' => true],
            ...$features,
        ];

        return new Network(
            version: 'test',
            modes: [self::BUS => 'bus', self::METRO => 'metro', self::TAXI => 'taxi', self::WALK => 'walk'],
            stops: [self::A => $stop(), self::B => $stop(), self::C => $stop(tactile: false)],
            lines: [
                1 => ['mode' => self::BUS, 'starts_at' => 300, 'ends_at' => 1320, 'frequency' => 10, 'fare' => 380],
                2 => ['mode' => self::METRO, 'starts_at' => 300, 'ends_at' => 1380, 'frequency' => 6, 'fare' => 160],
            ],
            departures: [
                self::A => [
                    $connection(1, self::A, self::B, self::BUS, 1, 10, null),
                    $connection(3, self::A, self::C, self::TAXI, null, 12, 2000),
                ],
                self::B => [
                    $connection(2, self::B, self::C, self::METRO, 2, 5, null, ['adapted' => false]),
                    $connection(4, self::B, self::C, self::WALK, null, 20, 0, ['has_stairs' => true]),
                ],
            ],
            integrations: [
                self::B.':'.self::BUS.':'.self::METRO => ['minutes' => 2, 'accessible' => true, 'fare_integrated' => true],
            ],
        );
    }
}
