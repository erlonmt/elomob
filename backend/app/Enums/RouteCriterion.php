<?php

namespace App\Enums;

/**
 * Critérios que o planejador calcula para cada busca.
 */
enum RouteCriterion: string
{
    case Fastest = 'fastest';
    case Cheapest = 'cheapest';
    case Transfers = 'transfers';
    case Sustainable = 'sustainable';

    public function label(): string
    {
        return match ($this) {
            self::Fastest => 'Mais rápido',
            self::Cheapest => 'Mais barato',
            self::Transfers => 'Menos trocas',
            self::Sustainable => 'Mais sustentável',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
