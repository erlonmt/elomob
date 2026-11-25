<?php

namespace App\Enums;

/**
 * Tipos de necessidade que o usuário marca no perfil. Cada tipo exige recursos
 * da rede em todo o trajeto; vários tipos somam as exigências.
 */
enum AccessibilityNeed: string
{
    case Wheelchair = 'wheelchair';
    case ReducedMobility = 'reduced_mobility';
    case Visual = 'visual';
    case Hearing = 'hearing';

    public function label(): string
    {
        return match ($this) {
            self::Wheelchair => 'Cadeira de rodas',
            self::ReducedMobility => 'Mobilidade reduzida',
            self::Visual => 'Deficiência visual',
            self::Hearing => 'Deficiência auditiva',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
