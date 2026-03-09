<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Unit;

use Jooservices\LaravelRepository\Support\Order;
use Jooservices\LaravelRepository\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrderTest extends TestCase
{
    #[Test]
    public function it_has_column_and_default_direction(): void
    {
        $order = new Order('created_at');
        $this->assertSame('created_at', $order->column);
        $this->assertSame('asc', $order->direction);
    }

    #[Test]
    public function it_accepts_custom_direction(): void
    {
        $order = new Order('name', 'desc');
        $this->assertSame('name', $order->column);
        $this->assertSame('desc', $order->direction);
    }
}
