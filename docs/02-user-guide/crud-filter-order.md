# CRUD, Filter, And Order

## CRUD behavior

`HasCrud` uses a fresh query for each operation. CRUD methods do not share the mutable filter chain state.

## Filter behavior

`HasFilter` accepts either key-value pairs or `FilterInterface` implementations. `get()` and `paginate()` execute the active query and then clear the stored builder.

## Order behavior

`HasOrder` accepts associative arrays or `Order` value objects and applies them to the active builder.

## Cursor pagination behavior

Repositories that also use `HasCursorPagination` can execute cursor-based pagination for large ordered result sets. If no explicit order has been applied, cursor pagination falls back to the model primary key in ascending order.

## Example

```php
use Jooservices\LaravelRepository\Support\Filter;
use Jooservices\LaravelRepository\Support\Order;

$users = $repository
    ->filter([
        new Filter('status', 'active'),
        new Filter('name', '%john%', 'like'),
    ])
    ->orderBy([new Order('created_at', 'desc')])
    ->get();

$cursorPage = $repository
    ->filter(['status' => 'active'])
    ->orderBy(['id' => 'asc'])
    ->cursorPaginate(15);
```
