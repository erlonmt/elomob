<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ObservabilityTest extends TestCase
{
    public function test_every_response_carries_a_request_id_that_the_logs_of_the_request_also_carry(): void
    {
        Route::get('/request-context', fn (): string => (string) Context::get('request_id'));
        $response = $this->get('/request-context');
        $this->assertSame($response->headers->get('X-Request-Id'), $response->getContent());

        $generated = $this->getJson('/api/v1/sessao')->assertOk()->headers->get('X-Request-Id');
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $generated);

        $this->getJson('/api/v1/sessao', ['X-Request-Id' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90'])
            ->assertHeader('X-Request-Id', 'a1b2c3d4e5f60718293a4b5c6d7e8f90');
        $this->assertNotSame('<script>', $this->getJson('/api/v1/sessao', ['X-Request-Id' => '<script>'])->headers->get('X-Request-Id'));

    }

    public function test_the_health_check_answers_ok_while_the_database_and_the_cache_answer(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_the_health_check_fails_when_the_database_does_not_answer(): void
    {
        DB::shouldReceive('select')->andThrow(new RuntimeException('Database unreachable.'));

        $this->get('/up')->assertInternalServerError();
    }
}
