<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Jooservices\LaravelRepository\Exceptions\RepositoryException;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RepositoryExceptionTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_message(): void
    {
        $e = new RepositoryException('Something failed');
        $this->assertSame('Something failed', $e->getMessage());
        $this->assertSame(0, $e->getCode());
    }

    #[Test]
    public function it_can_be_thrown_and_caught(): void
    {
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Test');
        throw new RepositoryException('Test');
    }
}
