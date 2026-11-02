<?php

namespace Tests\Unit;

use App\Enums\AccessibilityNeed;
use App\Models\Connection;
use App\Models\Stop;
use PHPUnit\Framework\TestCase;

class AccessibilityNeedTest extends TestCase
{
    public function test_requirements_follow_the_agreed_table(): void
    {
        $this->assertSame([
            'stops' => ['accessible'],
            'connection' => ['accessible' => true, 'adapted' => true, 'has_stairs' => false],
            'accessible_integration' => true,
        ], AccessibilityNeed::Wheelchair->requirements());
        $this->assertSame([
            'stops' => [],
            'connection' => ['has_stairs' => false],
            'accessible_integration' => true,
        ], AccessibilityNeed::ReducedMobility->requirements());
        $this->assertSame([
            'stops' => ['tactile_paving'],
            'connection' => ['audible_signals' => true],
            'accessible_integration' => false,
        ], AccessibilityNeed::Visual->requirements());
        $this->assertSame([
            'stops' => ['visual_signs'],
            'connection' => ['visual_signs' => true],
            'accessible_integration' => false,
        ], AccessibilityNeed::Hearing->requirements());
    }

    public function test_wheelchair_needs_are_a_superset_of_reduced_mobility(): void
    {
        $wheelchair = AccessibilityNeed::Wheelchair->requirements();
        $reducedMobility = AccessibilityNeed::ReducedMobility->requirements();

        $this->assertSame([], array_diff($reducedMobility['stops'], $wheelchair['stops']));
        $this->assertSame($reducedMobility['connection'], array_intersect_key($wheelchair['connection'], $reducedMobility['connection']));
        $this->assertTrue($wheelchair['accessible_integration']);
    }

    public function test_a_connection_meets_a_need_only_when_it_and_both_stops_have_the_features(): void
    {
        $connection = $this->connection(['audible_signals' => true], ['tactile_paving' => true], ['tactile_paving' => true]);
        $this->assertTrue(AccessibilityNeed::Visual->isMetBy($connection));

        $this->assertFalse(AccessibilityNeed::Visual->isMetBy($this->connection(['audible_signals' => false], ['tactile_paving' => true], ['tactile_paving' => true])));
        $this->assertFalse(AccessibilityNeed::Visual->isMetBy($this->connection(['audible_signals' => true], ['tactile_paving' => true], ['tactile_paving' => false])));
        $this->assertFalse(AccessibilityNeed::ReducedMobility->isMetBy($this->connection(['has_stairs' => true], [], [])));
        $this->assertTrue(AccessibilityNeed::ReducedMobility->isMetBy($this->connection(['has_stairs' => false, 'accessible' => false], [], [])));
        $this->assertFalse(AccessibilityNeed::Wheelchair->isMetBy($this->connection(['accessible' => true, 'adapted' => true, 'has_stairs' => false], ['accessible' => false], ['accessible' => true])));
    }

    public function test_a_connection_offers_a_need_along_its_way_whatever_its_stops_offer(): void
    {
        $this->assertTrue(AccessibilityNeed::Wheelchair->isMetAlong($this->connection(['accessible' => true, 'adapted' => true], [], [])));
        $this->assertFalse(AccessibilityNeed::Wheelchair->isMetAlong($this->connection(['accessible' => true, 'adapted' => false], ['accessible' => true], ['accessible' => true])));
        $this->assertFalse(AccessibilityNeed::ReducedMobility->isMetAlong($this->connection(['has_stairs' => true], [], [])));
        $this->assertTrue(AccessibilityNeed::Hearing->isMetAlong($this->connection(['visual_signs' => true], [], [])));
    }

    public function test_a_stop_the_passenger_stays_on_board_at_needs_no_features(): void
    {
        $passingBothStops = $this->connection(['audible_signals' => true], [], []);
        $this->assertTrue(AccessibilityNeed::Visual->isMetBy($passingBothStops, alreadyOnBoard: true, staysOnBoard: true));
        $this->assertFalse(AccessibilityNeed::Visual->isMetBy($passingBothStops, alreadyOnBoard: true));
        $this->assertFalse(AccessibilityNeed::Visual->isMetBy($passingBothStops, staysOnBoard: true));
        $this->assertTrue(AccessibilityNeed::Visual->isMetBy($this->connection(['audible_signals' => true], [], ['tactile_paving' => true]), alreadyOnBoard: true));
        $this->assertFalse(AccessibilityNeed::Visual->isMetBy($this->connection([], [], []), alreadyOnBoard: true, staysOnBoard: true));
    }

    public function test_a_stop_meets_a_need_only_with_every_stop_feature_it_requires(): void
    {
        $this->assertTrue(AccessibilityNeed::Hearing->isMetAt((new Stop)->forceFill(['visual_signs' => true])));
        $this->assertFalse(AccessibilityNeed::Hearing->isMetAt((new Stop)->forceFill(['visual_signs' => false, 'tactile_paving' => true])));
        $this->assertTrue(AccessibilityNeed::ReducedMobility->isMetAt((new Stop)->forceFill(['accessible' => false])));
    }

    /**
     * Build a connection between two stops, every feature off unless given.
     *
     * @param  array<string, bool>  $features
     * @param  array<string, bool>  $from
     * @param  array<string, bool>  $to
     */
    private function connection(array $features, array $from, array $to): Connection
    {
        $stopDefaults = ['accessible' => false, 'tactile_paving' => false, 'visual_signs' => false];
        $connection = (new Connection)->forceFill($features + ['accessible' => false, 'has_stairs' => false, 'adapted' => false, 'audible_signals' => false, 'visual_signs' => false]);
        $connection->setRelation('fromStop', (new Stop)->forceFill($from + $stopDefaults));
        $connection->setRelation('toStop', (new Stop)->forceFill($to + $stopDefaults));

        return $connection;
    }
}
