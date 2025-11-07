<?php

namespace Tests\Unit;

use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_environment_is_testing(): void
    {
        $this->assertEquals('testing', app()->environment());
    }

    public function test_database_connection_is_sqlite(): void
    {
        $this->assertEquals('sqlite', config('database.default'));
    }

    public function test_database_is_in_memory(): void
    {
        $this->assertEquals(':memory:', config('database.connections.sqlite.database'));
    }
}
