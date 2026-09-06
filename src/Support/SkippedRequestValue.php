<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Support;

/**
 * Sentinel returned by value-normalization rules when a filter clause must be skipped.
 */
final class SkippedRequestValue
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }
}
