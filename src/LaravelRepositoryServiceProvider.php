<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository;

use Illuminate\Support\ServiceProvider;

class LaravelRepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-repository.php', 'laravel-repository');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-repository.php' => config_path('laravel-repository.php'),
        ], 'laravel-repository-config');
    }
}
