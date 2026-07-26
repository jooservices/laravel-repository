<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Support;

use Illuminate\Http\Request;

final class RequestQueryInput
{
    /**
     * Resolve request-query payload using configured key priority.
     *
     * Both `filter` and `query` keys remain accepted; config only controls read order.
     */
    public static function resolve(Request $request): mixed
    {
        $primary = (string) config('laravel-repository.request_key', 'filter');
        $secondary = $primary === 'filter' ? 'query' : 'filter';

        return $request->input($primary) ?? $request->input($secondary) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolveArray(Request $request): array
    {
        $data = self::resolve($request);

        return is_array($data) ? $data : [];
    }
}
