<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use JOOservices\LaravelRepository\Support\RequestQueryInput;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequestQueryInputTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_filter_before_query_by_default(): void
    {
        Config::set('laravel-repository.request_key', 'filter');

        $request = Request::create('/', 'GET', [
            'filter' => ['where' => [['status', 'active']]],
            'query' => ['where' => [['status', 'pending']]],
        ]);

        $this->assertSame(['where' => [['status', 'active']]], RequestQueryInput::resolveArray($request));
    }

    #[Test]
    public function it_reads_query_before_filter_when_configured(): void
    {
        Config::set('laravel-repository.request_key', 'query');

        $request = Request::create('/', 'GET', [
            'filter' => ['where' => [['status', 'active']]],
            'query' => ['where' => [['status', 'pending']]],
        ]);

        $this->assertSame(['where' => [['status', 'pending']]], RequestQueryInput::resolveArray($request));
    }

    #[Test]
    public function it_falls_back_to_the_alternate_key(): void
    {
        Config::set('laravel-repository.request_key', 'query');

        $request = Request::create('/', 'GET', [
            'filter' => ['where' => [['status', 'active']]],
        ]);

        $this->assertSame(['where' => [['status', 'active']]], RequestQueryInput::resolveArray($request));
    }
}
