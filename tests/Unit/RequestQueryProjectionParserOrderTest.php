<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use JOOservices\LaravelRepository\Support\RequestQueryProjectionParser;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequestQueryProjectionParserOrderTest extends TestCase
{
    #[Test]
    public function it_parses_descending_shorthand(): void
    {
        $this->assertSame([
            ['column' => 'created_at', 'direction' => 'desc'],
        ], RequestQueryProjectionParser::parseOrder(['-created_at']));
    }

    #[Test]
    public function it_parses_comma_separated_shorthand(): void
    {
        $this->assertSame([
            ['column' => 'name', 'direction' => 'asc'],
            ['column' => 'id', 'direction' => 'desc'],
        ], RequestQueryProjectionParser::parseOrder(['name,-id']));
    }

    #[Test]
    public function it_parses_associative_direction_map(): void
    {
        $this->assertSame([
            ['column' => 'created_at', 'direction' => 'desc'],
        ], RequestQueryProjectionParser::parseOrder([
            'created_at' => 'desc',
        ]));
    }
}
