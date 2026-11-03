<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The API is reached through a proxy (the Next.js app, and nginx in Docker), which tells the client address in
 * X-Forwarded-For. The tests come from 127.0.0.1, the proxy phpunit.xml trusts, as the Next.js proxy does without
 * Docker. Requests carry no cookies, so each one starts a new session, as a script dropping its cookies would.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_client_behind_the_proxy_has_its_own_sign_in_limit(): void
    {
        foreach (range(1, 100) as $attempt) {
            $this->signIn("client{$attempt}@elomob.test", '203.0.113.10')->assertUnprocessable();
        }

        $this->signIn('one-more@elomob.test', '203.0.113.10')->assertTooManyRequests();
        $this->signIn('another-client@elomob.test', '203.0.113.20')->assertUnprocessable();
    }

    public function test_the_sign_in_limit_of_an_account_holds_whatever_address_the_attempts_come_from(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->signIn('target@elomob.test', "203.0.113.{$attempt}")->assertUnprocessable();
        }

        $this->signIn('target@elomob.test', '203.0.113.99')->assertTooManyRequests();
        $this->signIn('someone-else@elomob.test', '203.0.113.99')->assertUnprocessable();
    }

    public function test_a_guest_starting_a_new_session_for_each_search_is_still_limited_by_address(): void
    {
        foreach (range(1, 120) as $attempt) {
            $this->search('203.0.113.30')->assertUnprocessable();
        }

        $this->search('203.0.113.30')->assertTooManyRequests();
        $this->search('203.0.113.40')->assertUnprocessable();
    }

    /**
     * Try to sign in with a wrong password, from the given client address.
     */
    private function signIn(string $email, string $address): TestResponse
    {
        return $this->postJson('/api/v1/entrar', ['email' => $email, 'password' => 'wrong-pass-123'], ['X-Forwarded-For' => $address]);
    }

    /**
     * Send a search without its fields, which the API refuses after counting it, from the given client address.
     */
    private function search(string $address): TestResponse
    {
        return $this->postJson('/api/v1/buscas', [], ['X-Forwarded-For' => $address]);
    }
}
