<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests;

use JOOservices\LaravelRepository\LaravelRepositoryServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelRepositoryServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{name: string, email: string, status: string, score: int}
     */
    protected function fakeUserAttributes(array $overrides = []): array
    {
        $faker = fake();

        return array_merge([
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'status' => $faker->randomElement(['active', 'pending', 'inactive']),
            'score' => $faker->numberBetween(0, 100),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{name: string, email: string, status: string}
     */
    protected function fakeSoftUserAttributes(array $overrides = []): array
    {
        $faker = fake();

        return array_merge([
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'status' => $faker->randomElement(['active', 'pending', 'inactive']),
        ], $overrides);
    }
}
