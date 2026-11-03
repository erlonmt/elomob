<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmailCaseTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_an_account_is_stored_with_its_email_in_lower_case_and_signs_in_whatever_the_case(): void
    {
        $this->postJson('/api/v1/cadastrar', $this->registration('Ana.Souza@Elomob.TEST'))->assertCreated()
            ->assertJsonPath('data.user.email', 'ana.souza@elomob.test');
        $this->assertDatabaseHas('users', ['email' => 'ana.souza@elomob.test']);
        $this->postJson('/api/v1/sair')->assertOk();

        foreach (['ana.souza@elomob.test', 'ANA.SOUZA@ELOMOB.TEST', 'Ana.Souza@Elomob.Test'] as $email) {
            $this->postJson('/api/v1/entrar', ['email' => $email, 'password' => 'segredo-123'])->assertOk()
                ->assertJsonPath('data.user.email', 'ana.souza@elomob.test');
            $this->postJson('/api/v1/sair')->assertOk();
        }
    }

    public function test_an_email_differing_only_in_case_cannot_open_a_second_account(): void
    {
        User::factory()->create(['email' => 'ana.souza@elomob.test']);

        $this->postJson('/api/v1/cadastrar', $this->registration('ANA.Souza@elomob.test'))->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Este e-mail já está em uso.']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_a_profile_cannot_take_the_email_of_another_account_in_another_case_and_keeps_its_own_in_lower_case(): void
    {
        User::factory()->create(['email' => 'ana.souza@elomob.test']);
        $user = User::factory()->create(['email' => 'bruno@elomob.test']);
        $profile = ['name' => 'Bruno', 'preferred_route' => 'fastest', 'transport_modes' => ['bus']];

        $this->actingAs($user)->putJson('/api/v1/perfil', $profile + ['email' => 'Ana.Souza@Elomob.test'])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
        $this->putJson('/api/v1/perfil', $profile + ['email' => 'Bruno@Elomob.TEST'])->assertOk()
            ->assertJsonPath('data.email', 'bruno@elomob.test');
        $this->assertSame('bruno@elomob.test', $user->fresh()->email);
    }

    public function test_the_database_refuses_two_emails_differing_only_in_case(): void
    {
        User::factory()->create(['email' => 'ana.souza@elomob.test']);

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('users')->insert(['name' => 'Ana', 'email' => 'Ana.Souza@elomob.test', 'password' => 'hash']);
    }

    /**
     * @return array{name: string, email: string, password: string, password_confirmation: string}
     */
    private function registration(string $email): array
    {
        return ['name' => 'Ana Souza', 'email' => $email, 'password' => 'segredo-123', 'password_confirmation' => 'segredo-123'];
    }
}
