# Architecture

## Overview

The package follows a **trait-based composition** model: a single base repository class is extended, and behavior is added by implementing interfaces and using the corresponding traits. No behavior is forced; you use only what you need (YAGNI).

## Design principles

- **SOLID**: Interfaces define contracts; traits provide implementations. Single responsibility per trait.
- **KISS**: Simple method names and predictable signatures.
- **DRY**: Shared logic in `EloquentRepository` (query lifecycle); Filter/Order as reusable value objects.
- **YAGNI**: No base “kitchen sink” repository; compose via traits.

## Package structure

```
src/
├── Contracts/           # Interfaces (contracts)
│   ├── CrudRepositoryInterface.php
│   ├── FilterableRepositoryInterface.php
│   ├── FilterInterface.php
│   ├── OrderableRepositoryInterface.php
│   ├── RepositoryInterface.php          # Full stack (all four)
│   └── RequestQueryRepositoryInterface.php
├── Repositories/
│   └── EloquentRepository.php           # Base: model + query
├── Traits/              # Composable behavior
│   ├── HasCrud.php
│   ├── HasFilter.php
│   ├── HasOrder.php
│   └── HasRequestQuery.php
├── Support/             # Value objects & parsing
│   ├── Filter.php
│   ├── Order.php
│   └── RequestQueryParser.php
├── Exceptions/
│   └── RepositoryException.php
└── LaravelRepositoryServiceProvider.php
```

## Layers

| Layer | Role |
|-------|------|
| **Contracts** | Define what a repository can do (find, filter, order, fromRequest, etc.). |
| **EloquentRepository** | Holds the Eloquent model and manages the query builder instance (`getQuery()`, `newQuery()`). |
| **Traits** | Implement contract methods using the base query. Each trait is self-contained. |
| **Support** | `Filter` and `Order` value objects; `RequestQueryParser` turns request input into structured clauses. |

## Interface hierarchy

- **CrudRepositoryInterface**: find, findOrFail, all, create, update, delete.
- **FilterableRepositoryInterface**: filter, get, paginate, newQuery.
- **OrderableRepositoryInterface**: orderBy.
- **RequestQueryRepositoryInterface**: fromRequest.
- **RepositoryInterface**: extends all four (full-featured repository).

Implement only the interfaces you need; use the matching traits to satisfy them.

## Trait–interface mapping

| Need | Implement | Use traits |
|------|------------|------------|
| CRUD only | CrudRepositoryInterface | HasCrud |
| Filter + get/paginate | FilterableRepositoryInterface | HasFilter |
| Filter + order | FilterableRepositoryInterface, OrderableRepositoryInterface | HasFilter, HasOrder |
| Full CRUD + filter + order | RepositoryInterface (or the three interfaces) | HasCrud, HasFilter, HasOrder |
| Query from request | RequestQueryRepositoryInterface | HasFilter, HasOrder, HasRequestQuery |
| Full stack | RepositoryInterface | HasCrud, HasFilter, HasOrder, HasRequestQuery |

## Query lifecycle

1. **Base**: `EloquentRepository` stores an optional `$query` (Builder). `getQuery()` returns it or builds it via `newQuery()` from the model.
2. **Filter/Order/FromRequest**: Traits call `getQuery()` and apply constraints (where, orderBy, with, etc.). They return `$this` for chaining.
3. **Termination**: `get()` or `paginate()` (from HasFilter) runs the query and then clears `$query` so the next call starts fresh.

This keeps a single query per “operation” and avoids leaking state between requests when the repository is long-lived (e.g. in a controller).

## Configuration

- **config/laravel-repository.php**: `default_per_page`, `request_key` (key used for request input: `filter` or `query`). Published via `php artisan vendor:publish --tag=laravel-repository-config`.

## Service provider

- **LaravelRepositoryServiceProvider**: Merges default config and registers the publishable config. No global bindings; you instantiate repositories in your app (e.g. in controllers or service providers).
