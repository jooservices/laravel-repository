<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Jooservices\LaravelRepository\Support\Filter;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FilterTest extends TestCase
{
    #[Test]
    public function it_applies_where_with_default_operator(): void
    {
        $filter = new Filter('status', 'active');
        $this->assertSame('status', $filter->field);
        $this->assertSame('active', $filter->value);
        $this->assertSame('=', $filter->operator);

        $query = UserStub::query();
        $filter->apply($query);
        $this->assertStringContainsString('status', $query->toSql());
        $this->assertStringContainsString('?', $query->toSql());
    }

    #[Test]
    public function it_applies_where_with_custom_operator(): void
    {
        $filter = new Filter('name', '%john%', 'like');
        $this->assertSame('like', $filter->operator);

        $query = UserStub::query();
        $filter->apply($query);
        $this->assertStringContainsString('name', $query->toSql());
    }
}
