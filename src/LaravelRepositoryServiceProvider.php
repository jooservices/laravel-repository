<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository;

use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelRepository\Console\Commands\MakeRepositoryCommand;

final class LaravelRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-repository.php', 'laravel-repository');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/laravel-repository.php' => config_path('laravel-repository.php'),
        ], 'laravel-repository-config');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/laravel-repository'),
        ], 'laravel-repository-stubs');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRepositoryCommand::class,
            ]);
        }
    }
}
