<?php

namespace App\Services\Planning;

use App\Enums\RouteCriterion;
use App\Models\TransportMode;

/**
 * Calcula o melhor itinerário para um critério com o algoritmo de Dijkstra.
 *
 * O estado de busca é (parada, linha em que a pessoa está, modal anterior,
 * maior tarifa paga na cadeia de integrações). Assim a tarifa integrada, que
 * depende do que já foi pago, continua exata. A prioridade é uma tupla
 * comparada em ordem lexicográfica; toda etapa soma minutos, então nenhum
 * ciclo melhora o custo e a busca sempre termina.
 *
 * Regras de cada etapa:
 * - seguir na mesma linha não tem espera, troca nem nova tarifa;
 * - embarcar em uma linha espera metade da frequência, ou até o início do
 *   serviço, e não é possível depois do fim do serviço;
 * - trocar de modal em uma integração soma o tempo dela; com integração
 *   tarifária, paga só a diferença até a maior tarifa da cadeia;
 * - chegar a pé ou trocar sem integração tarifária cobra a tarifa cheia.
 */
class RoutePlanner
{
    public function plan(Network $network, int $originId, int $destinationId, RouteCriterion $criterion, PlanningOptions $options): ?PlannedRoute
    {
        if (! AccessibilityRules::stopAllows($network->stop($originId), $options->needs)
            || ! AccessibilityRules::stopAllows($network->stop($destinationId), $options->needs)) {
            return null;
        }

        $labels = [[
            'stop' => $originId, 'line' => null, 'mode' => null, 'chain_max' => 0,
            'minutes' => 0, 'cost' => 0, 'distance' => 0, 'rides' => 0, 'emissions' => 0,
            'parent' => null, 'step' => null,
        ]];

        $queue = new MinPriorityQueue;
        $queue->insert(0, $this->priority($criterion, $labels[0]));
        $settled = [];

        while (! $queue->isEmpty()) {
            $index = $queue->extract();
            $label = $labels[$index];
            $state = "{$label['stop']}|{$label['line']}|{$label['mode']}|{$label['chain_max']}";

            if (isset($settled[$state])) {
                continue;
            }
            $settled[$state] = true;

            if ($label['stop'] === $destinationId) {
                return $this->buildRoute($labels, $index, $criterion);
            }

            foreach ($network->departuresFrom($label['stop']) as $connection) {
                $next = $this->extend($network, $label, $connection, $options);

                if ($next !== null) {
                    $next['parent'] = $index;
                    $labels[] = $next;
                    $queue->insert(array_key_last($labels), $this->priority($criterion, $next));
                }
            }
        }

        return null;
    }

    private function extend(Network $network, array $label, array $connection, PlanningOptions $options): ?array
    {
        $modeId = $connection['mode'];
        $modeCode = $network->modeCode($modeId);
        $isWalk = $modeCode === TransportMode::WALK;

        if (! $isWalk && ! $options->allowsMode($modeId)) {
            return null;
        }
        if (! AccessibilityRules::connectionAllows($connection, $options->needs)) {
            return null;
        }

        $continuing = $connection['line'] !== null && $connection['line'] === $label['line'];
        $wait = 0;
        $transfer = 0;
        $cost = 0;
        $fareIntegrated = false;
        $chainMax = $label['chain_max'];
        $rides = $label['rides'];

        if (! $continuing) {
            // Desembarque e embarque nesta parada: ela precisa atender as necessidades.
            // A origem já foi verificada.
            if ($label['parent'] !== null && ! AccessibilityRules::stopAllows($network->stop($label['stop']), $options->needs)) {
                return null;
            }

            $integration = $label['mode'] === null ? null : $network->integration($label['stop'], $label['mode'], $modeId);

            if ($integration !== null) {
                if (! AccessibilityRules::integrationAllows($integration, $options->needs)) {
                    return null;
                }
                $transfer = $integration['minutes'];
            }

            $fare = $connection['line'] !== null ? $network->line($connection['line'])['fare'] : $connection['fare'];

            if ($integration !== null && $integration['fare_integrated'] && $chainMax > 0) {
                $cost = max(0, $fare - $chainMax);
                $chainMax = max($chainMax, $fare);
                $fareIntegrated = true;
            } else {
                $cost = $fare;
                $chainMax = $fare;
            }

            if ($connection['line'] !== null) {
                $wait = $this->waitForLine($network->line($connection['line']), $options, $label['minutes'] + $transfer);

                if ($wait === null) {
                    return null;
                }
            }

            if (! $isWalk) {
                $rides++;
            }
        }

        $stepMinutes = $connection['minutes'] + $wait + $transfer;

        return [
            'stop' => $connection['to'],
            'line' => $connection['line'],
            'mode' => $modeId,
            'chain_max' => $chainMax,
            'minutes' => $label['minutes'] + $stepMinutes,
            'cost' => $label['cost'] + $cost,
            'distance' => $label['distance'] + $connection['distance'],
            'rides' => $rides,
            'emissions' => $label['emissions'] + EmissionCalculator::grams($modeCode, $connection['distance']),
            'parent' => null,
            'step' => [
                'connection_id' => $connection['id'],
                'minutes' => $stepMinutes,
                'cost_cents' => $cost,
                'distance_meters' => $connection['distance'],
                'wait_minutes' => $wait,
                'transfer_minutes' => $transfer,
                'fare_integrated' => $fareIntegrated,
            ],
        ];
    }

    /** Espera para embarcar, ou null quando a linha já encerrou o serviço. */
    private function waitForLine(array $line, PlanningOptions $options, int $elapsedMinutes): ?int
    {
        $clock = ($options->departureMinuteOfDay() + $elapsedMinutes) % 1440;

        if ($clock < $line['starts_at']) {
            return $line['starts_at'] - $clock;
        }
        if ($clock > $line['ends_at']) {
            return null;
        }

        return intdiv($line['frequency'] + 1, 2);
    }

    /** @return list<int> */
    private function priority(RouteCriterion $criterion, array $label): array
    {
        $transfers = max(0, $label['rides'] - 1);

        return match ($criterion) {
            RouteCriterion::Fastest => [$label['minutes'], $transfers, $label['cost']],
            RouteCriterion::Cheapest => [$label['cost'], $label['minutes'], $transfers],
            RouteCriterion::Transfers => [$transfers, $label['minutes'], $label['cost']],
            RouteCriterion::Sustainable => [$label['emissions'], $label['minutes'], $label['cost']],
        };
    }

    private function buildRoute(array $labels, int $index, RouteCriterion $criterion): PlannedRoute
    {
        $final = $labels[$index];
        $steps = [];

        for ($i = $index; $labels[$i]['step'] !== null; $i = $labels[$i]['parent']) {
            $steps[] = $labels[$i]['step'];
        }

        $steps = array_reverse($steps);
        foreach ($steps as $position => &$step) {
            $step['sequence'] = $position + 1;
        }
        unset($step);

        return new PlannedRoute(
            criterion: $criterion,
            totalMinutes: $final['minutes'],
            totalCostCents: $final['cost'],
            totalDistanceMeters: $final['distance'],
            transfers: max(0, $final['rides'] - 1),
            emissionGrams: $final['emissions'],
            steps: $steps,
        );
    }
}
