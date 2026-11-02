<?php

namespace App\Enums;

/**
 * Codes of the seeded transport modes, in the order (and so with the ids) the seeder creates them.
 * The transport_modes table stays data: this enum is applied where the application relies on a code.
 */
enum TransportModeCode: string
{
    case Bus = 'bus';
    case Metro = 'metro';
    case Bike = 'bike';
    case Taxi = 'taxi';
    case App = 'app';
    case Walk = 'walk';

    /**
     * Modes a user may choose for a search or keep in their profile. Walking is never chosen: it is
     * always available to link stops, so it is not offered as an option.
     *
     * @return list<self>
     */
    public static function selectable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $mode): bool => $mode->isSelectable()));
    }

    /**
     * Codes of the modes a user may choose, as stored in the database and exchanged by the API.
     *
     * @return list<value-of<self>>
     */
    public static function selectableValues(): array
    {
        return array_column(self::selectable(), 'value');
    }

    /**
     * Whether the user may choose this mode.
     */
    public function isSelectable(): bool
    {
        return $this !== self::Walk;
    }

    /**
     * Whether travelling with this mode means boarding a vehicle, which may charge a fare and count as a transfer.
     */
    public function boardsVehicle(): bool
    {
        return $this !== self::Walk;
    }

    /**
     * Whether each ride with this mode is hired on its own, from stop to stop: two rides in a row are two services,
     * each charging its fare.
     */
    public function isHiredPerRide(): bool
    {
        return $this === self::Taxi || $this === self::App;
    }

    /**
     * Environmental impact weight added for every meter travelled with this mode.
     */
    public function impactPerMeter(): int
    {
        return match ($this) {
            self::Walk, self::Bike => 0,
            self::Metro => 1,
            self::Bus => 2,
            self::Taxi, self::App => 8,
        };
    }
}
