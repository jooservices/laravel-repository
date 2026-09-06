<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Unit;

use JOOservices\LaravelRepository\Support\RequestQueryValueNormalizer;
use JOOservices\LaravelRepository\Support\SkippedRequestValue;
use JOOservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequestQueryValueNormalizerTest extends TestCase
{
    #[Test]
    public function it_normalizes_nested_scalar_values(): void
    {
        $this->assertSame('example', RequestQueryValueNormalizer::normalize('  Example  ', ['trim', 'lowercase']));
        $this->assertSame('EXAMPLE', RequestQueryValueNormalizer::normalize(' example ', ['trim', 'uppercase']));
        $this->assertSame(['1', '2'], RequestQueryValueNormalizer::normalize([1, 2], ['string']));
        $this->assertSame([1, 2], RequestQueryValueNormalizer::normalize(['1', '2'], ['int']));
        $this->assertSame([1.5, 2.0], RequestQueryValueNormalizer::normalize(['1.5', '2'], ['float']));
        $this->assertSame(
            ['hello', ['world']],
            RequestQueryValueNormalizer::normalize(['  Hello ', [' WORLD ']], ['trim', 'lower']),
        );
        $this->assertSame(
            [true, false, 'maybe', true, false, 2],
            RequestQueryValueNormalizer::normalize(['true', 'false', 'maybe', 1, 0, 2], ['boolean']),
        );
    }

    #[Test]
    public function it_normalizes_array_csv_unique_and_null_rules(): void
    {
        $this->assertSame(['value'], RequestQueryValueNormalizer::normalize('value', ['array']));
        $this->assertSame(
            ['a', 'b', 'c'],
            RequestQueryValueNormalizer::normalize(' a, b ,, c ', [['rule' => 'csv'], 'unique']),
        );
        $this->assertSame(
            ['a', 'b', 3],
            RequestQueryValueNormalizer::normalize(['a|b', 'b', 3], [['rule' => 'csv', 'delimiter' => '|'], 'unique']),
        );
        $this->assertNull(RequestQueryValueNormalizer::normalize('   ', ['null_if_empty']));
        $this->assertNull(RequestQueryValueNormalizer::normalize([], ['null_if_empty']));
        $this->assertNull(RequestQueryValueNormalizer::normalize('NULL', ['null_if_literal']));
        $this->assertSame(0, RequestQueryValueNormalizer::normalize(0, ['null_if_empty']));
        $this->assertSame(5, RequestQueryValueNormalizer::normalize(5, ['null_if_literal']));
        $this->assertSame('value', RequestQueryValueNormalizer::normalize('value', ['unknown_rule']));
    }

    #[Test]
    public function it_ignores_invalid_rule_definitions(): void
    {
        $normalized = RequestQueryValueNormalizer::normalize(
            'A;B',
            [
                '',
                ['rule' => '   '],
                123,
                ['csv', ';'],
            ],
        );

        $this->assertSame(['A', 'B'], $normalized);
    }

    #[Test]
    public function ignore_rule_returns_skipped_sentinel_for_wildcard_values(): void
    {
        $skipped = RequestQueryValueNormalizer::normalize('*', ['ignore']);
        $this->assertInstanceOf(SkippedRequestValue::class, $skipped);
        $this->assertSame(SkippedRequestValue::instance(), $skipped);

        $this->assertInstanceOf(
            SkippedRequestValue::class,
            RequestQueryValueNormalizer::normalize('all', [['rule' => 'ignore']]),
        );
        $this->assertInstanceOf(
            SkippedRequestValue::class,
            RequestQueryValueNormalizer::normalize('', ['ignore']),
        );
        $this->assertSame(
            'active',
            RequestQueryValueNormalizer::normalize('active', ['ignore']),
        );
        $this->assertInstanceOf(
            SkippedRequestValue::class,
            RequestQueryValueNormalizer::normalize('any', [['rule' => 'ignore', 'values' => ['any', 'n/a']]]),
        );
    }

    #[Test]
    public function default_rule_fills_empty_values(): void
    {
        $this->assertSame(
            'fallback',
            RequestQueryValueNormalizer::normalize(null, [['rule' => 'default', 'value' => 'fallback']]),
        );
        $this->assertSame(
            'fallback',
            RequestQueryValueNormalizer::normalize('  ', [['rule' => 'default', 'value' => 'fallback']]),
        );
        $this->assertSame(
            'fallback',
            RequestQueryValueNormalizer::normalize([], [['rule' => 'default', 'value' => 'fallback']]),
        );
        $this->assertSame(
            'kept',
            RequestQueryValueNormalizer::normalize('kept', [['rule' => 'default', 'value' => 'fallback']]),
        );
        $this->assertSame(
            null,
            RequestQueryValueNormalizer::normalize(null, ['default']),
        );
    }

    #[Test]
    public function nullable_rule_nulls_empty_values(): void
    {
        $this->assertNull(RequestQueryValueNormalizer::normalize('', ['nullable']));
        $this->assertNull(RequestQueryValueNormalizer::normalize('   ', ['nullable']));
        $this->assertNull(RequestQueryValueNormalizer::normalize([], ['nullable']));
        $this->assertSame('value', RequestQueryValueNormalizer::normalize('value', ['nullable']));
        $this->assertSame(0, RequestQueryValueNormalizer::normalize(0, ['nullable']));
    }
}
