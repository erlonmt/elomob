<?php

namespace Tests\Feature;

use App\Models\TripSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TripSearchPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_a_signed_in_search_is_visible_only_to_its_account(): void
    {
        $owner = User::factory()->create();
        $search = TripSearch::factory()->for($owner)->create(['session_hash' => TripSearch::hashSessionKey('owner-session')]);

        $this->assertTrue(Gate::forUser($owner)->allows('view', [$search, null]));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('view', [$search, 'owner-session']));
        $this->assertFalse(Gate::forUser(null)->allows('view', [$search, 'owner-session']));
    }

    public function test_a_guest_search_is_visible_only_to_the_session_that_created_it(): void
    {
        $search = TripSearch::factory()->forGuestSession('guest-session')->create();

        $this->assertTrue(Gate::forUser(null)->allows('view', [$search, 'guest-session']));
        $this->assertTrue(Gate::forUser(User::factory()->create())->allows('view', [$search, 'guest-session']));
        $this->assertFalse(Gate::forUser(null)->allows('view', [$search, 'other-session']));
        $this->assertFalse(Gate::forUser(null)->allows('view', [$search, null]));
    }
}
