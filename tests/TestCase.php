<?php

namespace Unwahas\Video\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Unwahas\Video\VideoServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            VideoServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup in-memory sqlite database for testing migrations
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
