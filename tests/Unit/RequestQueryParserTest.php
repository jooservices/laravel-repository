<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Illuminate\Http\Request;
use Jooservices\LaravelRepository\Support\RequestQueryParser;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequestQueryParserTest extends TestCase
{
    #[Test]
    public function it_returns_empty_clauses_for_empty_payload(): void
    {
        $result = RequestQueryParser::parse([]);
        $this->assertEmpty($result['where']);
        $this->assertEmpty($result['order']);
        $this->assertEmpty($result['with']);
    }

    #[Test]
    public function it_parses_where_clauses(): void
    {
        $data = [
            'where' => [
                ['column' => 'status', 'value' => 'active'],
                ['column' => 'name', 'operator' => 'like', 'value' => '%x%'],
            ],
        ];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(2, $result['where']);
        $this->assertSame('status', $result['where'][0]['column']);
        $this->assertSame('=', $result['where'][0]['operator'] ?? '=');
        $this->assertSame('active', $result['where'][0]['value']);
        $this->assertSame('like', $result['where'][1]['operator']);
    }

    #[Test]
    public function it_parses_nested_filter_key(): void
    {
        $data = ['filter' => ['where' => [['column' => 'id', 'value' => 1]]]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(1, $result['where']);
        $this->assertSame('id', $result['where'][0]['column']);
    }

    #[Test]
    public function it_parses_order_clauses(): void
    {
        $data = [
            'order' => [
                ['column' => 'created_at', 'direction' => 'desc'],
                ['column' => 'name'],
            ],
        ];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(2, $result['order']);
        $this->assertSame('desc', $result['order'][0]['direction']);
        $this->assertSame('asc', $result['order'][1]['direction']);
    }

    #[Test]
    public function it_parses_where_in(): void
    {
        $data = ['whereIn' => [['column' => 'status', 'values' => ['a', 'b']]]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(1, $result['whereIn']);
        $this->assertSame(['a', 'b'], $result['whereIn'][0]['values']);
    }

    #[Test]
    public function it_parses_with(): void
    {
        $data = ['with' => ['profile', 'posts']];
        $result = RequestQueryParser::parse($data);
        $this->assertSame(['profile', 'posts'], $result['with']);
    }

    #[Test]
    public function it_parses_where_null(): void
    {
        $data = ['whereNull' => ['deleted_at']];
        $result = RequestQueryParser::parse($data);
        $this->assertSame(['deleted_at'], $result['whereNull']);
    }

    #[Test]
    public function it_parses_where_between(): void
    {
        $data = [
            'whereBetween' => [['column' => 'created_at', 'range' => ['2024-01-01', '2024-12-31']]],
        ];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(1, $result['whereBetween']);
        $this->assertSame('created_at', $result['whereBetween'][0]['column']);
        $this->assertSame(['2024-01-01', '2024-12-31'], $result['whereBetween'][0]['range']);
    }

    #[Test]
    public function it_parses_where_not_null(): void
    {
        $data = ['whereNotNull' => ['deleted_at']];
        $result = RequestQueryParser::parse($data);
        $this->assertSame(['deleted_at'], $result['whereNotNull']);
    }

    #[Test]
    public function it_parses_where_indexed_format(): void
    {
        $data = ['where' => [['status', 'active'], ['name', '!=', 'x']]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(2, $result['where']);
        $this->assertSame('status', $result['where'][0]['column']);
        $this->assertSame('active', $result['where'][0]['value']);
        $this->assertSame('!=', $result['where'][1]['operator']);
    }

    #[Test]
    public function from_request_returns_empty_for_non_array(): void
    {
        $request = Request::create('/', 'GET', ['filter' => 'not-array']);
        $result = RequestQueryParser::fromRequest($request);
        $this->assertEmpty($result['where']);
        $this->assertEmpty($result['order']);
    }

    #[Test]
    public function it_parses_nested_query_key(): void
    {
        $data = ['query' => ['where' => [['column' => 'id', 'value' => 1]]]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(1, $result['where']);
        $this->assertSame(1, $result['where'][0]['value']);
    }

    #[Test]
    public function it_skips_non_array_where_items(): void
    {
        $data = [
            'where' => [
                ['column' => 'a', 'value' => 1],
                'invalid',
                ['column' => 'b', 'value' => 2],
            ],
        ];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(2, $result['where']);
    }

    #[Test]
    public function it_skips_indexed_where_with_non_string_column(): void
    {
        $data = ['where' => [[123, 'value']]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(0, $result['where']);
    }

    #[Test]
    public function it_parses_order_indexed_format(): void
    {
        $data = ['order' => [['created_at', 'desc']]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(1, $result['order']);
        $this->assertSame('created_at', $result['order'][0]['column']);
        $this->assertSame('desc', $result['order'][0]['direction']);
    }

    #[Test]
    public function it_normalizes_order_direction_and_skips_empty_columns(): void
    {
        $data = [
            'order' => [
                ['column' => ' created_at ', 'direction' => 'DESC'],
                ['column' => 'name', 'direction' => 'sideways'],
                ['column' => '   ', 'direction' => 'desc'],
                [123, 'desc'],
            ],
        ];
        $result = RequestQueryParser::parse($data);

        $this->assertCount(2, $result['order']);
        $this->assertSame('created_at', $result['order'][0]['column']);
        $this->assertSame('desc', $result['order'][0]['direction']);
        $this->assertSame('name', $result['order'][1]['column']);
        $this->assertSame('asc', $result['order'][1]['direction']);
    }

    #[Test]
    public function it_parses_where_in_with_value_key(): void
    {
        $data = ['whereIn' => [['column' => 'status', 'value' => 'single']]];
        $result = RequestQueryParser::parse($data);
        $this->assertSame([['column' => 'status', 'values' => ['single']]], $result['whereIn']);
    }

    #[Test]
    public function it_parses_where_between_range_non_array(): void
    {
        $data = ['whereBetween' => [['column' => 'x', 'range' => 'invalid']]];
        $result = RequestQueryParser::parse($data);
        $this->assertSame([['column' => 'x', 'range' => []]], $result['whereBetween']);
    }

    #[Test]
    public function it_parses_where_null_with_array_item(): void
    {
        $data = ['whereNull' => [['column' => 'deleted_at']]];
        $result = RequestQueryParser::parse($data);
        $this->assertSame(['deleted_at'], $result['whereNull']);
    }

    #[Test]
    public function it_parses_with_as_single_string(): void
    {
        $data = ['with' => 'profile'];
        $result = RequestQueryParser::parse($data);
        $this->assertSame(['profile'], $result['with']);
    }

    #[Test]
    public function it_filters_empty_string_from_with(): void
    {
        $data = ['with' => ['profile', '', 'posts']];
        $result = RequestQueryParser::parse($data);
        $this->assertSame(['profile', 'posts'], $result['with']);
    }

    #[Test]
    public function it_skips_where_in_items_without_column(): void
    {
        $data = ['whereIn' => [['values' => [1, 2]], ['column' => 'id', 'values' => [1]]]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(1, $result['whereIn']);
        $this->assertSame('id', $result['whereIn'][0]['column']);
    }

    #[Test]
    public function it_skips_where_between_items_without_column(): void
    {
        $data = ['whereBetween' => [['range' => [1, 2]], ['column' => 'x', 'range' => [0, 1]]]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(1, $result['whereBetween']);
        $this->assertSame('x', $result['whereBetween'][0]['column']);
    }

    #[Test]
    public function it_parses_where_two_element_indexed(): void
    {
        $data = ['where' => [['status', 'active']]];
        $result = RequestQueryParser::parse($data);
        $this->assertCount(1, $result['where']);
        $this->assertSame('=', $result['where'][0]['operator']);
        $this->assertSame('active', $result['where'][0]['value']);
    }
}
