<?php

namespace App\Services\Planning;

use App\Enums\AccessibilityNeed;
use Carbon\CarbonImmutable;

/**
 * Opções de uma busca já resolvidas para o planejador.
 */
final class PlanningOptions
{
    /**
     * @param  CarbonImmutable  $departure  saída no fuso local da rede
     * @param  list<int>  $allowedModeIds  vazio permite todos; caminhada é sempre permitida
     * @param  list<AccessibilityNeed>  $needs
     */
    public function __construct(
        public readonly CarbonImmutable $departure,
        public readonly array $allowedModeIds = [],
        public readonly array $needs = [],
    ) {}

    public function departureMinuteOfDay(): int
    {
        return $this->departure->hour * 60 + $this->departure->minute;
    }

    public function allowsMode(int $modeId): bool
    {
        return $this->allowedModeIds === [] || in_array($modeId, $this->allowedModeIds, true);
    }
}
