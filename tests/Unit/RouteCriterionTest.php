<?php

namespace Tests\Unit;

use App\Enums\RouteCriterion;
use PHPUnit\Framework\TestCase;

class RouteCriterionTest extends TestCase
{
    public function test_criteria_follow_the_order_itineraries_are_produced(): void
    {
        $this->assertSame(['fastest', 'cheapest', 'transfers', 'sustainable'], array_column(RouteCriterion::cases(), 'value'));
    }

    public function test_sort_key_puts_the_optimized_measure_first_followed_by_its_tie_breakers(): void
    {
        $measures = ['minutes' => 10, 'cost' => 20, 'transfers' => 30, 'impact' => 40];

        $this->assertSame([10, 20, 30], RouteCriterion::Fastest->sortKey($measures));
        $this->assertSame([20, 10, 30], RouteCriterion::Cheapest->sortKey($measures));
        $this->assertSame([30, 10, 20], RouteCriterion::Transfers->sortKey($measures));
        $this->assertSame([40, 10, 20], RouteCriterion::Sustainable->sortKey($measures));
    }

    public function test_tie_breakers_decide_between_routes_equal_on_the_optimized_measure(): void
    {
        $slower = ['minutes' => 30, 'cost' => 380, 'transfers' => 0, 'impact' => 100];
        $faster = ['minutes' => 25, 'cost' => 380, 'transfers' => 1, 'impact' => 100];

        $this->assertSame(1, RouteCriterion::Cheapest->sortKey($slower) <=> RouteCriterion::Cheapest->sortKey($faster));
        $this->assertSame(1, RouteCriterion::Sustainable->sortKey($slower) <=> RouteCriterion::Sustainable->sortKey($faster));
        $this->assertSame(-1, RouteCriterion::Transfers->sortKey($slower) <=> RouteCriterion::Transfers->sortKey($faster));
        $this->assertSame(1, RouteCriterion::Fastest->sortKey($slower) <=> RouteCriterion::Fastest->sortKey($faster));
    }
}
