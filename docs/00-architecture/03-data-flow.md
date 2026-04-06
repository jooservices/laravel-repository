# Data Flow

## High-level flow

1. A controller, command, or service receives input.
2. A concrete repository extends `EloquentRepository` and composes the needed traits.
3. Trait methods obtain the current builder through `getQuery()` or use a fresh query for isolated CRUD work.
4. Terminal operations return a collection, paginator, model, or boolean result.
5. Filter chains clear query state after `get()` or `paginate()`.

## Request-query flow

1. `HasRequestQuery::fromRequest()` reads the request payload.
2. `RequestQueryParser::fromRequest()` resolves the `filter` or `query` key.
3. The parser converts supported clauses into normalized arrays.
4. The repository applies those clauses to the active builder.
5. `get()` or `paginate()` executes the query and resets state.

For the legacy visual flow page, see [process-flow.md](../process-flow.md).
