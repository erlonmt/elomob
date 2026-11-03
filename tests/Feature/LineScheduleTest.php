<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Line;
use App\Models\Stop;
use App\Services\Routing\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LineScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_a_bus_leaves_a_later_stop_of_its_line_as_many_minutes_after_the_first_as_the_stretches_before_take(): void
    {
        $modes = DB::table('transport_modes')->pluck('id', 'code');
        [$first, $second, $third] = Stop::factory()->count(3)->create()->modelKeys();
        $line = Line::factory()->create([
            'transport_mode_id' => $modes['bus'], 'starts_at' => '06:00:00', 'ends_at' => '22:00:00', 'frequency_minutes' => 30,
        ]);
        foreach ([[$first, $second], [$second, $first], [$second, $third], [$third, $second]] as [$from, $to]) {
            Connection::factory()->onLine($line)->create(['from_stop_id' => $from, 'to_stop_id' => $to, 'minutes' => 10, 'distance_meters' => 2000]);
        }

        $plan = app(RoutePlanner::class)->plan($second, $third, ['modes' => ['bus'], 'departure_at' => '2026-10-30T06:05:00-03:00']);

        $this->assertSame(5, $plan['fastest']->steps[0]['wait_minutes']);
    }

    public function test_the_lines_list_their_stops_in_order_each_way_with_the_minutes_from_the_first(): void
    {
        $line = $this->getJson('/api/v1/linhas?q=081')->assertOk()->json('data.0');

        $this->assertSame('Recife Antigo', $line['directions'][0]['to']);
        $this->assertSame(
            [['Várzea', 0], ['Terminal Integrado CDU', 8], ['Cordeiro', 18], ['Derby', 32], ['Boa Vista', 40], ['Recife Antigo', 50]],
            array_map(fn (array $stop): array => [$stop['name'], $stop['minutes']], $line['directions'][0]['stops']),
        );
        $this->assertSame('Várzea', $line['directions'][1]['to']);
        $this->assertSame(['Recife Antigo', 0], [$line['directions'][1]['stops'][0]['name'], $line['directions'][1]['stops'][0]['minutes']]);
    }
}
