<?php

namespace App\Services\Planning;

/**
 * Retrato da rede em arrays simples, para caber no cache e ser percorrido
 * rapidamente pelo planejador. Horários das linhas ficam em minutos do dia.
 */
final class Network
{
    /**
     * @param  array<int, string>  $modes  id => código do modal
     * @param  array<int, array{accessible: bool, tactile_paving: bool, visual_signs: bool}>  $stops
     * @param  array<int, array{mode: int, starts_at: int, ends_at: int, frequency: int, fare: int}>  $lines
     * @param  array<int, list<array<string, mixed>>>  $departures  parada => conexões que saem dela
     * @param  array<string, array{minutes: int, accessible: bool, fare_integrated: bool}>  $integrations
     */
    public function __construct(
        public readonly string $version,
        private readonly array $modes,
        private readonly array $stops,
        private readonly array $lines,
        private readonly array $departures,
        private readonly array $integrations,
    ) {}

    public function hasStop(int $id): bool
    {
        return isset($this->stops[$id]);
    }

    /** @return array{accessible: bool, tactile_paving: bool, visual_signs: bool} */
    public function stop(int $id): array
    {
        return $this->stops[$id];
    }

    /** @return array{mode: int, starts_at: int, ends_at: int, frequency: int, fare: int} */
    public function line(int $id): array
    {
        return $this->lines[$id];
    }

    public function modeCode(int $id): string
    {
        return $this->modes[$id];
    }

    public function modeId(string $code): ?int
    {
        $id = array_search($code, $this->modes, true);

        return $id === false ? null : $id;
    }

    /** @return list<array<string, mixed>> */
    public function departuresFrom(int $stopId): array
    {
        return $this->departures[$stopId] ?? [];
    }

    /** @return array{minutes: int, accessible: bool, fare_integrated: bool}|null */
    public function integration(int $stopId, int $fromModeId, int $toModeId): ?array
    {
        return $this->integrations["{$stopId}:{$fromModeId}:{$toModeId}"] ?? null;
    }

    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'modes' => $this->modes,
            'stops' => $this->stops,
            'lines' => $this->lines,
            'departures' => $this->departures,
            'integrations' => $this->integrations,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['version'],
            $data['modes'],
            $data['stops'],
            $data['lines'],
            $data['departures'],
            $data['integrations'],
        );
    }
}
