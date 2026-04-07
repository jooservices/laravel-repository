<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Jooservices\LaravelRepository\Contracts\RequestFilterInterface;

trait HasRequestFilters
{
    /**
     * @var array<string,
     *     RequestFilterInterface|class-string<RequestFilterInterface>|Closure(Builder<*>, mixed): void
     * >
     */
    protected array $requestFilters = [];

    /**
     * @return array<string,
     *     RequestFilterInterface|class-string<RequestFilterInterface>|Closure(Builder<*>, mixed): void
     * >
     */
    public function requestFilters(): array
    {
        $filters = [];

        foreach ($this->requestFilters as $name => $filter) {
            if (! is_string($name)) {
                continue;
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            if ($filter instanceof RequestFilterInterface || $filter instanceof Closure) {
                $filters[$name] = $filter;

                continue;
            }

            if (is_string($filter) && is_subclass_of($filter, RequestFilterInterface::class)) {
                $filters[$name] = $filter;
            }
        }

        return $filters;
    }
}
