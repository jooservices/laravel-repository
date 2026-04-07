<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Jooservices\LaravelRepository\Exceptions\InvalidRequestQueryException;
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

    #[Test]
    public function it_builds_disallowed_query_messages(): void
    {
        $this->assertSame(
            'Sort [email] is not allowed. Allowed sorts: name, created_at.',
            InvalidRequestQueryException::disallowedSort('email', ['name', 'created_at'])->getMessage(),
        );
        $this->assertSame(
            'Scope [active] does not exist on the repository model.',
            InvalidRequestQueryException::unknownScope('active')->getMessage(),
        );

        $supportedClauses = [
            'where',
            'orWhere',
            'whereIn',
            'whereBetween',
            'whereNull',
            'whereNotNull',
            'fields',
            'filters',
            'scope',
            'whereHas',
            'with',
            'order',
        ];

        $unsupportedClauseMessage = 'Request query clause [aggregate] is not supported. '
            .'Supported clauses: where, orWhere, whereIn, whereBetween, whereNull, whereNotNull, '
            .'fields, filters, scope, whereHas, with, order.';

        $this->assertSame(
            $unsupportedClauseMessage,
            InvalidRequestQueryException::unsupportedClause('aggregate', $supportedClauses)->getMessage(),
        );
    }
}
