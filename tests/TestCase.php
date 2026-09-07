<?php

namespace SalsabilEnnaiem\PvModule\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SalsabilEnnaiem\PvModule\PvModuleServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PvModuleServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('pv-module.user_model', \SalsabilEnnaiem\PvModule\Tests\Models\User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../tests/database/migrations');
    }
}