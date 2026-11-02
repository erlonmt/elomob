<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\Integration;
use App\Models\Line;
use App\Models\Stop;
use App\Models\TransportMode;
use Illuminate\Database\Seeder;

/**
 * Seeds the demonstration network of Recife. Every record is matched by its natural unique key, so running the
 * seeder again updates the rows in place and keeps their ids stable for existing favorites and searches.
 *
 * Accessibility features start from "everything offered, no stairs" and the barriers below take them away. They
 * are chosen so that each accessibility need changes the trip from Boa Viagem to Recife Antigo.
 */
class MobilitySeeder extends Seeder
{
    /**
     * Code, name and color of each transport mode, in the order of the TransportModeCode enum.
     *
     * @var list<array{string, string, string}>
     */
    private const array MODES = [
        ['bus', 'Ônibus', '#028090'],
        ['metro', 'Metrô', '#3f51b5'],
        ['bike', 'Bicicleta', '#2ec4b6'],
        ['taxi', 'Táxi', '#ffb300'],
        ['app', 'Aplicativo', '#9c27b0'],
        ['walk', 'Caminhada', '#526b74'],
    ];

    /**
     * Name, address, latitude, longitude and main mode of each stop.
     *
     * @var list<array{string, string, float, float, string}>
     */
    private const array STOPS = [
        ['Boa Viagem', 'Av. Boa Viagem', -8.119, -34.899, 'bus'],
        ['Estação Joana Bezerra', 'TI Joana Bezerra', -8.074, -34.897, 'metro'],
        ['Estação Recife', 'Praça Visconde de Mauá', -8.069, -34.882, 'metro'],
        ['Recife Antigo', 'Praça do Marco Zero', -8.063, -34.871, 'bus'],
        ['Cordeiro', 'Av. Caxangá', -8.043, -34.932, 'bus'],
        ['Casa Amarela', 'Estrada do Arraial', -8.024, -34.918, 'bus'],
        ['Várzea', 'Cidade Universitária', -8.049, -34.950, 'bus'],
        ['Derby', 'Av. Agamenon Magalhães, 1500', -8.057, -34.900, 'bus'],
        ['Afogados', 'Largo da Paz', -8.080, -34.909, 'bus'],
        ['Boa Vista', 'Rua da Aurora', -8.056, -34.884, 'bus'],
        ['Graças', 'Rua das Graças, 142', -8.039, -34.902, 'bus'],
        ['Terminal Integrado CDU', 'Av. Caxangá', -8.036, -34.947, 'bus'],
        ['Bike Recife — Est. 12', 'Estação demonstrativa Boa Viagem', -8.117, -34.897, 'bike'],
        ['Pina', 'Av. Herculano Bandeira', -8.090, -34.885, 'bus'],
        ['Cais de Santa Rita', 'Terminal Cais de Santa Rita', -8.068, -34.878, 'bus'],
        ['Estação Imbiribeira', 'Av. Mascarenhas de Morais', -8.0995, -34.9105, 'metro'],
        ['Estação Aeroporto', 'Praça Ministro Salgado Filho', -8.1265, -34.9177, 'metro'],
    ];

    /**
     * Stops with a bike sharing station.
     *
     * @var list<string>
     */
    private const array BIKE_STATIONS = ['Bike Recife — Est. 12', 'Recife Antigo'];

    /**
     * Features each stop lacks: steps at the entrance ("accessible"), tactile paving and an information panel.
     *
     * @var array<string, array<string, false>>
     */
    private const array STOP_BARRIERS = [
        'Estação Recife' => ['accessible' => false],
        'Casa Amarela' => ['accessible' => false, 'tactile_paving' => false, 'visual_signs' => false],
        'Várzea' => ['tactile_paving' => false, 'visual_signs' => false],
        'Graças' => ['tactile_paving' => false, 'visual_signs' => false],
        'Bike Recife — Est. 12' => ['tactile_paving' => false, 'visual_signs' => false],
        'Afogados' => ['visual_signs' => false],
        'Cais de Santa Rita' => ['tactile_paving' => false],
    ];

    /**
     * Code, name, mode, first and last departure, frequency in minutes and fare in cents of each line.
     *
     * @var list<array{string, string, string, string, string, int, int}>
     */
    private const array LINES = [
        ['042', 'Boa Viagem → Centro', 'bus', '05:00', '23:00', 12, 380],
        ['081', 'Cordeiro → Recife Antigo', 'bus', '05:30', '22:30', 15, 380],
        ['033', 'Casa Amarela → Afogados', 'bus', '05:15', '22:00', 18, 380],
        ['CENTRO', 'Linha Centro', 'metro', '05:00', '23:00', 10, 160],
        ['SUL', 'Linha Sul', 'metro', '05:00', '23:00', 15, 160],
        ['061', 'Boa Viagem → Cais de Santa Rita (Via Mangue)', 'bus', '05:30', '22:00', 20, 380],
        ['BRT', 'BRT Caxangá (TI CDU → Boa Vista)', 'bus', '05:30', '22:30', 10, 380],
    ];

    /**
     * Features the vehicles or platforms of a line lack: an accessible way ("accessible"), a lift or ramp
     * ("adapted"), audible or visual signs; "has_stairs" marks a platform reached only by stairs.
     *
     * @var array<string, array<string, bool>>
     */
    private const array LINE_BARRIERS = [
        '042' => ['visual_signs' => false],
        '081' => ['visual_signs' => false],
        '033' => ['adapted' => false, 'visual_signs' => false],
        'CENTRO' => ['accessible' => false, 'has_stairs' => true, 'visual_signs' => false],
    ];

    /**
     * Features the connections without a line lack, by mode: app cars are not adapted, and a bike ride is neither
     * adapted nor gives audible guidance.
     *
     * @var array<string, array<string, false>>
     */
    private const array MODE_BARRIERS = [
        'app' => ['adapted' => false],
        'bike' => ['adapted' => false, 'audible_signals' => false],
    ];

    /**
     * Stretches, in both directions: origin, destination, mode, line, minutes, meters, own fare (only without a line)
     * and the features the stretch lacks. On foot, "audible_signals" is an audible crossing signal.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: ?string, 4: int, 5: int, 6: ?int, 7?: array<string, false>}>
     */
    private const array STRETCHES = [
        ['Boa Viagem', 'Estação Joana Bezerra', 'bus', '042', 18, 4200, null],
        ['Estação Joana Bezerra', 'Estação Recife', 'metro', 'CENTRO', 10, 1800, null],
        ['Estação Recife', 'Recife Antigo', 'walk', null, 7, 550, 0, ['audible_signals' => false]],
        ['Boa Viagem', 'Recife Antigo', 'taxi', null, 25, 7800, 1850],
        ['Boa Viagem', 'Recife Antigo', 'app', null, 29, 7800, 1600],
        ['Boa Viagem', 'Bike Recife — Est. 12', 'walk', null, 3, 200, 0],
        ['Bike Recife — Est. 12', 'Recife Antigo', 'bike', null, 42, 8200, 380],
        ['Cordeiro', 'Derby', 'bus', '081', 14, 3500, null],
        ['Derby', 'Boa Vista', 'bus', '081', 8, 1800, null],
        ['Boa Vista', 'Recife Antigo', 'bus', '081', 10, 2100, null],
        ['Casa Amarela', 'Graças', 'bus', '033', 12, 2600, null],
        ['Graças', 'Derby', 'bus', '033', 8, 1900, null],
        ['Derby', 'Afogados', 'bus', '033', 15, 3200, null],
        ['Afogados', 'Estação Joana Bezerra', 'walk', null, 12, 950, 0, ['accessible' => false]],
        ['Várzea', 'Terminal Integrado CDU', 'bus', '081', 8, 1800, null],
        ['Terminal Integrado CDU', 'Cordeiro', 'bus', '081', 10, 2200, null],
        ['Estação Joana Bezerra', 'Estação Recife', 'metro', 'SUL', 13, 1800, null],
        ['Estação Joana Bezerra', 'Estação Imbiribeira', 'metro', 'SUL', 6, 3300, null],
        ['Estação Imbiribeira', 'Estação Aeroporto', 'metro', 'SUL', 6, 3200, null],
        ['Boa Viagem', 'Pina', 'bus', '061', 18, 4500, null],
        ['Pina', 'Cais de Santa Rita', 'bus', '061', 22, 5000, null],
        ['Terminal Integrado CDU', 'Cordeiro', 'bus', 'BRT', 10, 2200, null],
        ['Cordeiro', 'Derby', 'bus', 'BRT', 14, 3500, null],
        ['Derby', 'Boa Vista', 'bus', 'BRT', 8, 1800, null],
        ['Cais de Santa Rita', 'Recife Antigo', 'walk', null, 12, 950, 0],
        ['Boa Vista', 'Recife Antigo', 'walk', null, 18, 1300, 0],
    ];

    /**
     * Stop, mode arrived with, mode left with, transfer minutes, description, whether the transfer is step-free and
     * whether it integrates the fare. Each integration is also registered in the reverse direction, after all the
     * forward ones. Bike sharing is a separate system, so changing to or from a bike pays its own fare.
     *
     * @var list<array{string, string, string, int, string, bool, bool}>
     */
    private const array INTEGRATIONS = [
        ['Estação Joana Bezerra', 'bus', 'metro', 3, 'Terminal integrado: ônibus 042 e Linhas Centro e Sul do metrô', true, true],
        ['Estação Recife', 'metro', 'bike', 2, 'Estação de bicicletas demonstrativa, com degraus na saída', false, false],
        ['Terminal Integrado CDU', 'bus', 'bike', 5, 'Integração demonstrativa na CDU', true, false],
    ];

    /**
     * Every accessibility feature offered, before the barriers take some away.
     *
     * @var array<string, bool>
     */
    private const array STOP_FEATURES = ['accessible' => true, 'tactile_paving' => true, 'visual_signs' => true];

    /**
     * @var array<string, bool>
     */
    private const array CONNECTION_FEATURES = ['accessible' => true, 'has_stairs' => false, 'adapted' => true, 'audible_signals' => true, 'visual_signs' => true];

    /**
     * Taxi and app rides between any two stops at least ON_DEMAND_MINIMUM_METERS apart (closer ones are walked), unless
     * a stretch above registers the ride. A ride follows the straight line times ROAD_DETOUR at ROAD_METERS_PER_MINUTE,
     * and an app ride adds the wait for the car. Fares are a flag fall plus a price per kilometre; both, like the
     * speed, are calibrated on the registered rides from Boa Viagem to Recife Antigo.
     *
     * @var array<string, array{flag_fall: int, per_kilometre: int, pickup_minutes: int}>
     */
    private const array ON_DEMAND_RIDES = [
        'taxi' => ['flag_fall' => 450, 'per_kilometre' => 180, 'pickup_minutes' => 0],
        'app' => ['flag_fall' => 400, 'per_kilometre' => 155, 'pickup_minutes' => 4],
    ];

    private const int ON_DEMAND_MINIMUM_METERS = 1000;

    private const float ROAD_DETOUR = 1.15;

    private const int ROAD_METERS_PER_MINUTE = 312;

    public function run(): void
    {
        foreach (self::MODES as [$code, $name, $color]) {
            TransportMode::updateOrCreate(['code' => $code], compact('name', 'color'));
        }
        $modes = TransportMode::pluck('id', 'code');

        foreach (self::STOPS as [$name, $address, $latitude, $longitude, $mode]) {
            $hasBikeStation = in_array($name, self::BIKE_STATIONS, true);
            Stop::updateOrCreate(['name' => $name], [
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'transport_mode_id' => $modes[$mode],
                'bikes_available' => $hasBikeStation ? 4 : null,
                'docks_available' => $hasBikeStation ? 8 : null,
                ...self::STOP_FEATURES,
                ...(self::STOP_BARRIERS[$name] ?? []),
            ]);
        }
        $stops = Stop::pluck('id', 'name');

        foreach (self::LINES as [$code, $name, $mode, $startsAt, $endsAt, $frequency, $fare]) {
            Line::updateOrCreate(['code' => $code], [
                'name' => $name,
                'transport_mode_id' => $modes[$mode],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'frequency_minutes' => $frequency,
                'fare_cents' => $fare,
            ]);
        }
        $lines = Line::pluck('id', 'code');

        foreach (self::STRETCHES as $stretch) {
            [$from, $to, $mode, $line, $minutes, $distance, $fare] = $stretch;
            $features = [
                ...self::CONNECTION_FEATURES,
                ...($line === null ? self::MODE_BARRIERS[$mode] ?? [] : self::LINE_BARRIERS[$line] ?? []),
                ...($stretch[7] ?? []),
            ];
            foreach ([[$from, $to], [$to, $from]] as [$origin, $destination]) {
                Connection::updateOrCreate([
                    'from_stop_id' => $stops[$origin],
                    'to_stop_id' => $stops[$destination],
                    'transport_mode_id' => $modes[$mode],
                    'line_id' => $line === null ? null : $lines[$line],
                ], ['minutes' => $minutes, 'distance_meters' => $distance, 'boarding_fare_cents' => $fare, ...$features]);
            }
        }

        $registered = [];
        foreach (self::STRETCHES as [$from, $to, $mode]) {
            $registered["{$from}|{$to}|{$mode}"] = $registered["{$to}|{$from}|{$mode}"] = true;
        }
        foreach (self::STOPS as [$from, , $fromLatitude, $fromLongitude]) {
            foreach (self::STOPS as [$to, , $toLatitude, $toLongitude]) {
                $straightMeters = $this->straightMeters($fromLatitude, $fromLongitude, $toLatitude, $toLongitude);
                if ($from === $to || $straightMeters < self::ON_DEMAND_MINIMUM_METERS) {
                    continue;
                }
                $roadMeters = (int) round($straightMeters * self::ROAD_DETOUR);
                $driveMinutes = (int) ceil($roadMeters / self::ROAD_METERS_PER_MINUTE);
                foreach (self::ON_DEMAND_RIDES as $mode => $ride) {
                    if (isset($registered["{$from}|{$to}|{$mode}"])) {
                        continue;
                    }
                    Connection::updateOrCreate([
                        'from_stop_id' => $stops[$from],
                        'to_stop_id' => $stops[$to],
                        'transport_mode_id' => $modes[$mode],
                        'line_id' => null,
                    ], [
                        'minutes' => $driveMinutes + $ride['pickup_minutes'],
                        'distance_meters' => $roadMeters,
                        'boarding_fare_cents' => $ride['flag_fall'] + (int) round($ride['per_kilometre'] * $roadMeters / 1000),
                        ...self::CONNECTION_FEATURES,
                        ...(self::MODE_BARRIERS[$mode] ?? []),
                    ]);
                }
            }
        }

        foreach ([false, true] as $reversed) {
            foreach (self::INTEGRATIONS as [$stop, $arrivedWith, $leftWith, $minutes, $description, $accessible, $fareIntegrated]) {
                [$fromMode, $toMode] = $reversed ? [$leftWith, $arrivedWith] : [$arrivedWith, $leftWith];
                Integration::updateOrCreate(
                    ['stop_id' => $stops[$stop], 'from_mode_id' => $modes[$fromMode], 'to_mode_id' => $modes[$toMode]],
                    ['transfer_minutes' => $minutes, 'description' => $description, 'accessible' => $accessible, 'fare_integrated' => $fareIntegrated],
                );
            }
        }
    }

    /**
     * Distance in meters along the Earth's surface between two coordinates (haversine formula).
     */
    private function straightMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $haversine = sin($latitudeDelta / 2) ** 2 + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return 2 * 6371000 * asin(sqrt($haversine));
    }
}
