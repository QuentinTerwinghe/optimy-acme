<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * The .env.testing file is loaded in tests/bootstrap.php before the application is created.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Override configuration with testing values AFTER bootstrap
        // This ensures tests run with the correct configuration regardless of .env file
        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        // Also set the environment on the application instance itself
        $app->detectEnvironment(fn () => 'testing');

        return $app;
    }
}
