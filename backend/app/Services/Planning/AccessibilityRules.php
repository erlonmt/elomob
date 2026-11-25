<?php

namespace App\Services\Planning;

use App\Enums\AccessibilityNeed;

/**
 * Recursos que cada tipo de necessidade exige das paradas, das conexões e das
 * integrações (tabela "Acessibilidade" em docs/BANCO_DE_DADOS.md).
 */
final class AccessibilityRules
{
    /**
     * Vale para as paradas em que a pessoa está: origem, destino, embarques,
     * desembarques e as que percorre a pé.
     *
     * @param  list<AccessibilityNeed>  $needs
     */
    public static function stopAllows(array $stop, array $needs): bool
    {
        foreach ($needs as $need) {
            $ok = match ($need) {
                AccessibilityNeed::Wheelchair => $stop['accessible'],
                AccessibilityNeed::ReducedMobility => true,
                AccessibilityNeed::Visual => $stop['tactile_paving'],
                AccessibilityNeed::Hearing => $stop['visual_signs'],
            };

            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    /** @param  list<AccessibilityNeed>  $needs */
    public static function connectionAllows(array $connection, array $needs): bool
    {
        foreach ($needs as $need) {
            $ok = match ($need) {
                AccessibilityNeed::Wheelchair => $connection['accessible'] && $connection['adapted'] && ! $connection['has_stairs'],
                AccessibilityNeed::ReducedMobility => ! $connection['has_stairs'],
                AccessibilityNeed::Visual => $connection['audible_signals'],
                AccessibilityNeed::Hearing => $connection['visual_signs'],
            };

            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    /** @param  list<AccessibilityNeed>  $needs */
    public static function integrationAllows(array $integration, array $needs): bool
    {
        foreach ($needs as $need) {
            if (in_array($need, [AccessibilityNeed::Wheelchair, AccessibilityNeed::ReducedMobility], true)
                && ! $integration['accessible']) {
                return false;
            }
        }

        return true;
    }
}
