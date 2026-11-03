<?php

namespace Tests\Unit;

use App\Services\Routing\LineRoutes;
use PHPUnit\Framework\TestCase;

class LineRoutesTest extends TestCase
{
    public function test_a_line_runs_both_ways_from_its_ends_and_reaches_each_stop_after_the_stretches_before_it(): void
    {
        $routes = LineRoutes::fromStretches([
            $this->stretch(7, 1, 2, 10),
            $this->stretch(7, 2, 1, 11),
            $this->stretch(7, 2, 3, 5),
            $this->stretch(7, 3, 2, 6),
            $this->stretch(null, 1, 3, 30),
        ]);

        $this->assertSame([
            [['stop' => 1, 'minutes' => 0], ['stop' => 2, 'minutes' => 10], ['stop' => 3, 'minutes' => 15]],
            [['stop' => 3, 'minutes' => 0], ['stop' => 2, 'minutes' => 6], ['stop' => 1, 'minutes' => 17]],
        ], $routes->directions(7));
        $this->assertSame(0, $routes->departureOffset(7, 1, 2));
        $this->assertSame(10, $routes->departureOffset(7, 2, 3));
        $this->assertSame(0, $routes->departureOffset(7, 3, 2));
        $this->assertSame(6, $routes->departureOffset(7, 2, 1));
    }

    public function test_a_line_that_is_not_a_single_path_leaves_every_stop_at_the_line_times(): void
    {
        $routes = LineRoutes::fromStretches([
            $this->stretch(8, 1, 2, 10),
            $this->stretch(8, 2, 3, 10),
            $this->stretch(8, 2, 4, 10),
        ]);

        $this->assertSame([], $routes->directions(8));
        $this->assertSame(0, $routes->departureOffset(8, 2, 3));
        $this->assertSame(0, $routes->departureOffset(99, 1, 2));
    }

    /**
     * @return array{line_id: ?int, from_stop_id: int, to_stop_id: int, minutes: int}
     */
    private function stretch(?int $lineId, int $from, int $to, int $minutes): array
    {
        return ['line_id' => $lineId, 'from_stop_id' => $from, 'to_stop_id' => $to, 'minutes' => $minutes];
    }
}
