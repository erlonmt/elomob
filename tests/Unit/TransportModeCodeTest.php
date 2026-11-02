<?php

namespace Tests\Unit;

use App\Enums\TransportModeCode;
use PHPUnit\Framework\TestCase;

class TransportModeCodeTest extends TestCase
{
    public function test_codes_follow_the_seeded_order(): void
    {
        $this->assertSame(['bus', 'metro', 'bike', 'taxi', 'app', 'walk'], array_column(TransportModeCode::cases(), 'value'));
    }

    public function test_every_mode_but_walking_is_selectable_and_boards_a_vehicle(): void
    {
        $this->assertSame(
            [TransportModeCode::Bus, TransportModeCode::Metro, TransportModeCode::Bike, TransportModeCode::Taxi, TransportModeCode::App],
            TransportModeCode::selectable(),
        );
        $this->assertSame(['bus', 'metro', 'bike', 'taxi', 'app'], TransportModeCode::selectableValues());
        $this->assertFalse(TransportModeCode::Walk->isSelectable());
        $this->assertFalse(TransportModeCode::Walk->boardsVehicle());
        foreach (TransportModeCode::selectable() as $mode) {
            $this->assertTrue($mode->isSelectable());
            $this->assertTrue($mode->boardsVehicle());
        }
    }

    public function test_impact_per_meter_weights_each_mode(): void
    {
        $weights = array_map(fn (TransportModeCode $mode): int => $mode->impactPerMeter(), TransportModeCode::cases());

        $this->assertSame([2, 1, 0, 8, 8, 0], $weights);
    }

    public function test_only_taxi_and_app_rides_are_hired_one_by_one(): void
    {
        $this->assertSame(
            ['taxi', 'app'],
            array_column(array_values(array_filter(TransportModeCode::cases(), fn (TransportModeCode $mode): bool => $mode->isHiredPerRide())), 'value'),
        );
    }
}
