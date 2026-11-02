<?php

namespace Tests\Feature;

use App\Models\Itinerary;
use App\Models\ItineraryStep;
use App\Models\SavedPlace;
use App\Models\TripSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_neither_change_a_password_nor_clear_a_history_nor_delete_an_account(): void
    {
        $this->putJson('/api/v1/perfil/senha', $this->passwordChange())->assertUnauthorized();
        $this->deleteJson('/api/v1/historico')->assertUnauthorized();
        $this->deleteJson('/api/v1/perfil', ['password' => 'old-pass-123'])->assertUnauthorized();
    }

    public function test_changing_the_password_requires_the_current_one_and_a_confirmed_new_one(): void
    {
        $user = User::factory()->create(['password' => 'old-pass-123']);
        $this->actingAs($user);

        $this->putJson('/api/v1/perfil/senha', $this->passwordChange(['current_password' => 'wrong-pass-123']))->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password' => 'A senha atual está incorreta.']);
        $this->putJson('/api/v1/perfil/senha', $this->passwordChange(['password' => 'short', 'password_confirmation' => 'short']))->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'O campo nova senha deve ter pelo menos 8 caracteres.']);
        $this->putJson('/api/v1/perfil/senha', $this->passwordChange(['password_confirmation' => 'other-pass-456']))->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'A confirmação de nova senha não confere.']);
        $this->assertTrue(Hash::check('old-pass-123', $user->fresh()->password));
    }

    public function test_a_new_password_replaces_the_old_one_and_signs_out_the_other_sessions(): void
    {
        $user = User::factory()->create(['password' => 'old-pass-123']);
        $oldPasswordHash = $user->password;

        $this->actingAs($user)->putJson('/api/v1/perfil/senha', $this->passwordChange())->assertOk()
            ->assertJsonPath('data.email', $user->email)->assertJsonMissingPath('data.password');

        $this->assertTrue(Hash::check('new-pass-456', $user->fresh()->password));
        $this->getJson('/api/v1/perfil')->assertOk();
        $this->withSession(['password_hash_web' => $oldPasswordHash])->getJson('/api/v1/perfil')->assertUnauthorized();
        $this->assertGuest();
        $this->postJson('/api/v1/entrar', ['email' => $user->email, 'password' => 'old-pass-123'])->assertUnprocessable();
        $this->postJson('/api/v1/entrar', ['email' => $user->email, 'password' => 'new-pass-456'])->assertOk();
    }

    public function test_the_session_keeps_its_csrf_token_after_a_password_change(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'old-pass-123']));
        $this->startSession();
        $token = session()->token();

        $this->putJson('/api/v1/perfil/senha', $this->passwordChange())->assertOk();

        $this->assertSame($token, session()->token());
    }

    public function test_clearing_the_history_deletes_only_the_searches_of_the_account(): void
    {
        $user = User::factory()->create();
        $searches = TripSearch::factory()->count(2)->for($user)->create();
        ItineraryStep::factory()->for(Itinerary::factory()->for($searches[0]))->create();
        $favorite = SavedPlace::factory()->for($user)->create();
        $otherSearch = TripSearch::factory()->create();

        $this->actingAs($user)->deleteJson('/api/v1/historico')->assertOk()->assertJsonPath('data.deleted', 2);

        $this->getJson('/api/v1/historico')->assertOk()->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('itineraries', 0);
        $this->assertDatabaseCount('itinerary_steps', 0);
        $this->assertModelExists($favorite);
        $this->assertModelExists($otherSearch);
        $this->assertModelExists($user);
    }

    public function test_deleting_the_account_requires_its_password(): void
    {
        $user = User::factory()->create(['password' => 'old-pass-123']);

        $this->actingAs($user)->deleteJson('/api/v1/perfil', ['password' => 'wrong-pass-123'])->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'A senha está incorreta.']);
        $this->deleteJson('/api/v1/perfil')->assertUnprocessable()->assertJsonValidationErrors(['password' => 'O campo senha é obrigatório.']);

        $this->assertModelExists($user);
        $this->assertAuthenticatedAs($user);
    }

    public function test_deleting_the_account_erases_its_favorites_and_searches_and_signs_out(): void
    {
        $user = User::factory()->create(['password' => 'old-pass-123']);
        $search = TripSearch::factory()->for($user)->create();
        ItineraryStep::factory()->for(Itinerary::factory()->for($search))->create();
        $favorite = SavedPlace::factory()->for($user)->create();
        $otherSearch = TripSearch::factory()->create();
        $otherFavorite = SavedPlace::factory()->create();

        $this->actingAs($user)->deleteJson('/api/v1/perfil', ['password' => 'old-pass-123'])->assertOk()
            ->assertJsonPath('data.user', null)->assertJsonStructure(['data' => ['csrf_token']]);

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertModelMissing($search);
        $this->assertModelMissing($favorite);
        $this->assertDatabaseCount('itinerary_steps', 0);
        $this->assertModelExists($otherSearch);
        $this->assertModelExists($otherFavorite);
        $this->postJson('/api/v1/entrar', ['email' => $user->email, 'password' => 'old-pass-123'])->assertUnprocessable();
    }

    public function test_password_checks_are_limited_per_account(): void
    {
        [$user, $otherUser] = User::factory()->count(2)->create(['password' => 'old-pass-123']);

        $this->actingAs($user);
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->deleteJson('/api/v1/perfil', ['password' => 'wrong-pass-123'])->assertUnprocessable();
        }
        $this->putJson('/api/v1/perfil/senha', $this->passwordChange())->assertTooManyRequests();

        $this->flushSession();
        $this->actingAs($otherUser)->deleteJson('/api/v1/perfil', ['password' => 'wrong-pass-123'])->assertUnprocessable();
    }

    /**
     * A valid password change from "old-pass-123" to "new-pass-456", with the given fields replaced.
     *
     * @param  array<string, string>  $changes
     * @return array<string, string>
     */
    private function passwordChange(array $changes = []): array
    {
        return array_replace(['current_password' => 'old-pass-123', 'password' => 'new-pass-456', 'password_confirmation' => 'new-pass-456'], $changes);
    }
}
