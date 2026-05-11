<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jooservices\LaravelRepository\Support\QueryOperator;
use Jooservices\LaravelRepository\Support\RequestQueryHasParser;
use Jooservices\LaravelRepository\Support\RequestQueryProjectionParser;
use Jooservices\LaravelRepository\Support\RequestQueryRelationParser;
use Jooservices\LaravelRepository\Support\RequestQueryScopeParser;
use Jooservices\LaravelRepository\Tests\Stubs\UserStub;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequestQuerySupportClassesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function query_operator_applies_all_supported_operator_variants(): void
    {
        UserStub::create(['name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active']);
        UserStub::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'status' => 'pending']);

        $query = UserStub::query();
        QueryOperator::apply($query, 'where', 'name', 'exact', 'John Doe');
        $this->assertCount(1, $query->get());

        $query = UserStub::query();
        QueryOperator::apply($query, 'where', 'name', 'partial', 'John');
        $this->assertCount(1, $query->get());

        $query = UserStub::query();
        QueryOperator::apply($query, 'where', 'name', 'begins_with', 'Ja');
        $this->assertCount(1, $query->get());

        $query = UserStub::query();
        QueryOperator::apply($query, 'where', 'email', 'ends with', 'example.com');
        $this->assertCount(2, $query->get());

        $query = UserStub::query();
        QueryOperator::apply($query, 'where', 'status', '!=', 'pending');
        $this->assertCount(1, $query->get());

        $query = UserStub::query();
        QueryOperator::apply($query, 'where', 'id', 'gte', 1);
        $this->assertCount(2, $query->get());

        $query = UserStub::query();
        QueryOperator::apply($query, 'where', 'status', 'neq', 'pending');
        $this->assertCount(1, $query->get());

        $this->assertSame('beginswith', QueryOperator::normalize(' begins_with '));
        $this->assertTrue(QueryOperator::isSupported('ends with'));
        $this->assertTrue(QueryOperator::isSupported('lte'));
        $this->assertFalse(QueryOperator::isSupported('regexp'));
    }

    #[Test]
    public function request_query_has_parser_normalizes_valid_and_invalid_items(): void
    {
        $result = RequestQueryHasParser::parse([
            ['relation' => ' posts ', 'operator' => '??', 'count' => '-2'],
            ['comments', '>', 2],
            ['likes', '4'],
            'invalid',
            [123, 1],
        ]);

        $this->assertSame([
            ['relation' => 'posts', 'operator' => '>=', 'count' => 0],
            ['relation' => 'comments', 'operator' => '>', 'count' => 2],
            ['relation' => 'likes', 'operator' => '>=', 'count' => 4],
        ], $result);
    }

    #[Test]
    public function request_query_projection_parser_normalizes_fields_filters_with_and_order(): void
    {
        $this->assertSame(
            ['name', 'email', 'status'],
            RequestQueryProjectionParser::parseFields(['name,email', ' status ', 123, 'name']),
        );

        $this->assertSame(
            ['search' => 'john', 'status' => 'active'],
            RequestQueryProjectionParser::parseFilters([
                ' search ' => 'john',
                'status' => 'active',
                '   ' => 'skip',
                1 => 'skip',
            ]),
        );

        $this->assertSame(
            ['profile', 'posts'],
            RequestQueryProjectionParser::parseWith(['profile', '', 123, 'posts']),
        );

        $this->assertSame([
            ['column' => 'created_at', 'direction' => 'desc'],
            ['column' => 'name', 'direction' => 'asc'],
        ], RequestQueryProjectionParser::parseOrder([
            ['column' => ' created_at ', 'direction' => 'DESC'],
            ['name', 'sideways'],
            ['column' => '   '],
            'invalid',
        ]));
    }

    #[Test]
    public function request_query_relation_parser_normalizes_supported_clause_shapes(): void
    {
        $result = RequestQueryRelationParser::parse([
            [
                'relation' => ' posts ',
                'where' => [['column' => 'status', 'value' => 'published']],
                'orWhere' => [['name', 'partial', 'post']],
                'whereIn' => [['column' => 'status', 'value' => ['published', 'draft']]],
                'whereBetween' => [['column' => 'id', 'value' => [1, 3]]],
                'whereNull' => [['column' => 'deleted_at']],
                'whereNotNull' => ['created_at'],
            ],
            ['comments'],
            [123],
            'invalid',
            ['relation' => '   '],
        ]);

        $this->assertCount(2, $result);
        $this->assertSame('posts', $result[0]['relation']);
        $this->assertSame('published', $result[0]['where'][0]['value']);
        $this->assertSame('partial', $result[0]['orWhere'][0]['operator']);
        $this->assertSame(['published', 'draft'], $result[0]['whereIn'][0]['values']);
        $this->assertSame([1, 3], $result[0]['whereBetween'][0]['range']);
        $this->assertSame(['deleted_at'], $result[0]['whereNull']);
        $this->assertSame(['created_at'], $result[0]['whereNotNull']);
        $this->assertSame('comments', $result[1]['relation']);
    }

    #[Test]
    public function request_query_scope_parser_normalizes_string_and_array_items(): void
    {
        $result = RequestQueryScopeParser::parse([
            ' active ',
            ['email_domain', 'test.com'],
            ['name' => 'verified', 'parameters' => 'yes'],
            ['name' => 123],
            ['name' => '   '],
            123,
        ]);

        $this->assertSame([
            ['name' => 'active', 'parameters' => []],
            ['name' => 'email_domain', 'parameters' => ['test.com']],
            ['name' => 'verified', 'parameters' => ['yes']],
        ], $result);
    }
}
