<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against anything other than an isolated test database.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $isIsolatedDatabase = $database === ':memory:' || str_contains(mb_strtolower($database), 'test');

        if (! $this->app->environment('testing') || ! $isIsolatedDatabase) {
            throw new RuntimeException("Testes interrompidos: ambiente [{$this->app->environment()}] e banco [{$connection}:{$database}] não são isolados.");
        }
    }
}
