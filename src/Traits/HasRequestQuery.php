<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Traits;

use Illuminate\Http\Request;
use Jooservices\LaravelRepository\Support\RequestQueryParser;

trait HasRequestQuery
{
    public function fromRequest(Request $request): static
    {
        $clauses = RequestQueryParser::fromRequest($request);
        $query = $this->getQuery();

        foreach ($clauses['where'] as $where) {
            $query->where(
                $where['column'],
                $where['operator'],
                $where['value']
            );
        }
        foreach ($clauses['orWhere'] as $where) {
            $query->orWhere(
                $where['column'],
                $where['operator'],
                $where['value']
            );
        }
        foreach ($clauses['whereIn'] as $whereIn) {
            $query->whereIn($whereIn['column'], $whereIn['values']);
        }
        foreach ($clauses['whereBetween'] as $whereBetween) {
            if (count($whereBetween['range']) >= 2) {
                $query->whereBetween($whereBetween['column'], $whereBetween['range']);
            }
        }
        foreach ($clauses['whereNull'] as $column) {
            $query->whereNull($column);
        }
        foreach ($clauses['whereNotNull'] as $column) {
            $query->whereNotNull($column);
        }
        foreach ($clauses['with'] as $relation) {
            $query->with($relation);
        }
        foreach ($clauses['order'] as $order) {
            $query->orderBy($order['column'], $order['direction']);
        }

        return $this;
    }
}
