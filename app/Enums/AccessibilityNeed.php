<?php

namespace App\Enums;

use App\Models\Connection;
use App\Models\Stop;

/**
 * Kinds of disability a person may declare in their profile. Each one lists the accessibility features the
 * route planner requires along the trip; several needs add up their requirements. Stop features count wherever the
 * passenger is at the stop: where the trip starts and ends and where they board, get off or walk past, but not at a
 * stop their vehicle only goes through.
 */
enum AccessibilityNeed: string
{
    case Wheelchair = 'wheelchair';
    case ReducedMobility = 'reduced_mobility';
    case Visual = 'visual';
    case Hearing = 'hearing';

    /**
     * Features a trip must offer: boolean stop columns that must be true at the stops the passenger is at,
     * connection columns with the value they must have, and whether changing modes needs an accessible integration.
     *
     * @return array{stops: list<string>, connection: array<string, bool>, accessible_integration: bool}
     */
    public function requirements(): array
    {
        return match ($this) {
            self::Wheelchair => [
                'stops' => ['accessible'],
                'connection' => ['accessible' => true, 'adapted' => true, 'has_stairs' => false],
                'accessible_integration' => true,
            ],
            self::ReducedMobility => [
                'stops' => [],
                'connection' => ['has_stairs' => false],
                'accessible_integration' => true,
            ],
            self::Visual => [
                'stops' => ['tactile_paving'],
                'connection' => ['audible_signals' => true],
                'accessible_integration' => false,
            ],
            self::Hearing => [
                'stops' => ['visual_signs'],
                'connection' => ['visual_signs' => true],
                'accessible_integration' => false,
            ],
        };
    }

    /**
     * Whether the stop offers what this need requires of the stops the passenger is at.
     */
    public function isMetAt(Stop $stop): bool
    {
        return array_all($this->requirements()['stops'], fn (string $column): bool => $stop->getAttribute($column) === true);
    }

    /**
     * Whether the connection offers what this need requires, and so do its stops, except one the passenger stays on
     * board at: the first when they were already on the vehicle, the last when they stay on it for the next
     * connection. Integrations are left out: they depend on the mode the trip arrives with. Requires the
     * "fromStop" and "toStop" relations.
     */
    public function isMetBy(Connection $connection, bool $alreadyOnBoard = false, bool $staysOnBoard = false): bool
    {
        return $this->isMetAlong($connection)
            && ($alreadyOnBoard || $this->isMetAt($connection->fromStop))
            && ($staysOnBoard || $this->isMetAt($connection->toStop));
    }

    /**
     * Whether the connection itself (its vehicle or its way on foot) offers what this need requires, leaving its
     * stops aside.
     */
    public function isMetAlong(Connection $connection): bool
    {
        foreach ($this->requirements()['connection'] as $column => $value) {
            if ($connection->getAttribute($column) !== $value) {
                return false;
            }
        }

        return true;
    }
}
