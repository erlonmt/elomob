<?php

namespace Database\Factories;

use App\Models\Stop;
use App\Models\TransportMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stop>
 */
class StopFactory extends Factory
{
    /**
     * Neighbourhoods of Recife used to name fake stops.
     *
     * @var list<string>
     */
    private const array NEIGHBOURHOODS = ['Boa Viagem', 'Pina', 'Espinheiro', 'Aflitos', 'Torre', 'Madalena', 'Casa Forte', 'Poço da Panela', 'Encruzilhada', 'Santo Amaro', 'Imbiribeira', 'Ipsep', 'Iputinga', 'Engenho do Meio'];

    /**
     * Streets of Recife used to build fake addresses.
     *
     * @var list<string>
     */
    private const array STREETS = ['Av. Conselheiro Aguiar', 'Av. Domingos Ferreira', 'Rua do Hospício', 'Av. Norte Miguel Arraes de Alencar', 'Av. Rui Barbosa', 'Rua da Hora', 'Av. Recife', 'Rua Real da Torre'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(self::NEIGHBOURHOODS).' — Parada '.fake()->unique()->numberBetween(1, 99999),
            'address' => fake()->randomElement(self::STREETS).', '.fake()->numberBetween(1, 3000),
            'latitude' => fake()->latitude(-8.15, -7.95),
            'longitude' => fake()->longitude(-35.0, -34.87),
            'transport_mode_id' => TransportMode::factory(),
            'accessible' => true,
            'tactile_paving' => true,
            'visual_signs' => true,
            'bikes_available' => null,
            'docks_available' => null,
        ];
    }

    /**
     * Indicate that the stop is a bike sharing station with the given availability.
     */
    public function bikeStation(int $bikesAvailable = 4, int $docksAvailable = 8): static
    {
        return $this->state(fn (array $attributes): array => [
            'bikes_available' => $bikesAvailable,
            'docks_available' => $docksAvailable,
        ]);
    }
}
