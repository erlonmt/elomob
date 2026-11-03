<?php

namespace App\Services;

/**
 * The order of the stops along each line, worked out from its stretches. A line whose stretches form a single path
 * runs both ways from its two ends, each way leaving its first stop at the line's times, so a vehicle leaves a later
 * stop as many minutes later as the stretches before it take. A line of any other shape (a loop or a branch) is
 * taken as leaving every stop at the line's times.
 */
final readonly class LineRoutes
{
    /**
     * @param  array<int, list<list<array{stop: int, minutes: int}>>>  $directions  Each line's two ways: its stops in order, with the minutes from the first one.
     * @param  array<int, array<int, array<int, int>>>  $offsets  Minutes from the first stop until a vehicle leaves a stop, by line, stop and next stop.
     */
    private function __construct(private array $directions, private array $offsets) {}

    /**
     * Work out the lines from their stretches; stretches without a line are ignored.
     *
     * @param  iterable<array{line_id: ?int, from_stop_id: int, to_stop_id: int, minutes: int}>  $stretches
     */
    public static function fromStretches(iterable $stretches): self
    {
        $minutesByLine = [];
        foreach ($stretches as $stretch) {
            if ($stretch['line_id'] !== null) {
                $minutesByLine[$stretch['line_id']][$stretch['from_stop_id']][$stretch['to_stop_id']] = $stretch['minutes'];
            }
        }
        $directions = [];
        $offsets = [];
        foreach ($minutesByLine as $lineId => $minutes) {
            $path = self::path($minutes);
            if ($path === null) {
                continue;
            }
            foreach ([$path, array_reverse($path)] as $stops) {
                $direction = self::timed($stops, $minutes);
                $directions[$lineId][] = $direction;
                foreach (array_slice($direction, 0, -1) as $index => $stop) {
                    $offsets[$lineId][$stop['stop']][$direction[$index + 1]['stop']] = $stop['minutes'];
                }
            }
        }

        return new self($directions, $offsets);
    }

    /**
     * The two ways of a line, from the end with the lower stop id first; empty when the line is not a single path.
     *
     * @return list<list<array{stop: int, minutes: int}>>
     */
    public function directions(int $lineId): array
    {
        return $this->directions[$lineId] ?? [];
    }

    /**
     * Minutes from the first stop of the way until a vehicle of the line leaves the stop toward the next one.
     */
    public function departureOffset(int $lineId, int $fromStopId, int $toStopId): int
    {
        return $this->offsets[$lineId][$fromStopId][$toStopId] ?? 0;
    }

    /**
     * The stops of a line in order from one end to the other, or null when its stretches do not form a single path.
     *
     * @param  array<int, array<int, int>>  $minutes
     * @return list<int>|null
     */
    private static function path(array $minutes): ?array
    {
        $neighbours = [];
        foreach ($minutes as $from => $destinations) {
            foreach (array_keys($destinations) as $to) {
                $neighbours[$from][$to] = true;
                $neighbours[$to][$from] = true;
            }
        }
        $ends = array_keys(array_filter($neighbours, fn (array $next): bool => count($next) === 1));
        if (count($ends) !== 2 || array_any($neighbours, fn (array $next): bool => count($next) > 2)) {
            return null;
        }
        $path = [min($ends)];
        $previous = null;
        while (count($path) < count($neighbours)) {
            $current = $path[array_key_last($path)];
            $next = array_values(array_filter(array_keys($neighbours[$current]), fn (int $stop): bool => $stop !== $previous));
            if ($next === []) {
                return null;
            }
            $previous = $current;
            $path[] = $next[0];
        }

        return $path;
    }

    /**
     * The stops with the minutes from the first one, taking each stretch the way it is ridden (or the other way when
     * only that one is registered).
     *
     * @param  list<int>  $stops
     * @param  array<int, array<int, int>>  $minutes
     * @return list<array{stop: int, minutes: int}>
     */
    private static function timed(array $stops, array $minutes): array
    {
        $elapsed = 0;
        $timed = [['stop' => $stops[0], 'minutes' => 0]];
        foreach (array_slice($stops, 1) as $index => $stop) {
            $previous = $stops[$index];
            $elapsed += $minutes[$previous][$stop] ?? $minutes[$stop][$previous];
            $timed[] = ['stop' => $stop, 'minutes' => $elapsed];
        }

        return $timed;
    }
}
