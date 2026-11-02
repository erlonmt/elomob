<?php

namespace Tests\Feature;

use App\Models\Itinerary;
use App\Models\ItineraryStep;
use App\Models\TripSearch;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestSearchPruningTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_pruning_deletes_guest_searches_older_than_a_week_with_their_itineraries_and_steps(): void
    {
        $oldGuestSearch = TripSearch::factory()->forGuestSession('old-guest')->create(['created_at' => now()->subDays(8)]);
        $itinerary = Itinerary::factory()->for($oldGuestSearch)->create();
        $step = ItineraryStep::factory()->for($itinerary)->create();
        $recentGuestSearch = TripSearch::factory()->forGuestSession('recent-guest')->create(['created_at' => now()->subDays(6)]);
        $oldAccountSearch = TripSearch::factory()->create(['created_at' => now()->subMonth()]);

        $this->artisan('model:prune', ['--model' => [TripSearch::class]])->assertSuccessful();

        $this->assertModelMissing($oldGuestSearch);
        $this->assertModelMissing($itinerary);
        $this->assertModelMissing($step);
        $this->assertModelExists($recentGuestSearch);
        $this->assertModelExists($oldAccountSearch);
    }

    public function test_pruning_is_scheduled_daily(): void
    {
        $pruning = collect(app(Schedule::class)->events())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'model:prune'));

        $this->assertCount(1, $pruning);
        $this->assertSame('0 0 * * *', $pruning->first()->expression);
    }
}
