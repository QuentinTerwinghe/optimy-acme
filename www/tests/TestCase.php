<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup the test environment.
     *
     * This method is called before each test method is executed.
     * It resets the permission cache to prevent cache pollution between tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions before each test
        // This prevents cache pollution between tests when using Spatie Laravel-Permission
        // Even with CACHE_STORE=array, the PermissionRegistrar maintains an in-memory cache
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
