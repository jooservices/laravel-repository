<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Jooservices\LaravelRepository\Support\RequestQueryValueNormalizer;
use Jooservices\LaravelRepository\Tests\TestCase;
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
}
