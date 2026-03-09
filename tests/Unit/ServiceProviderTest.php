<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Jooservices\LaravelRepository\LaravelRepositoryServiceProvider;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_without_error(): void
    {
        $provider = $this->app->getProvider(LaravelRepositoryServiceProvider::class);
        $this->assertInstanceOf(LaravelRepositoryServiceProvider::class, $provider);
    }

    #[Test]
    public function config_can_be_merged(): void
    {
        $this->assertSame(15, config('laravel-repository.default_per_page'));
        $this->assertSame('filter', config('laravel-repository.request_key'));
    }
}
