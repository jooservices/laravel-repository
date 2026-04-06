# Request Query Support

Repositories that use `HasRequestQuery` can hydrate query constraints from request input under the `filter` or `query` key.

## Supported clause families

- `where`
- `orWhere`
- `whereIn`
- `whereBetween`
- `whereNull`
- `whereNotNull`
- `with`
- `order`

## Controller example

```php
$users = $repository->fromRequest($request)->paginate(15);
```

## Important boundary

The docs should not claim support for request-query clauses beyond those parsed by `RequestQueryParser` and covered by tests.
