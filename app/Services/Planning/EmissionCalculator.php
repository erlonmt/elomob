<?php

namespace App\Services\Planning;

/**
 * Emissão estimada de um trecho, em gramas de CO2 por passageiro.
 */
final class EmissionCalculator
{
    public static function grams(string $modeCode, int $distanceMeters): int
    {
        $factor = config("elomob.emission_grams_per_km.{$modeCode}", 0);

        return (int) round($distanceMeters / 1000 * $factor);
    }
}
