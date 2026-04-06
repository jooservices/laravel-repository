# Trait-Based Composition

Use only the traits your repository actually needs.

| Need | Implement | Use traits |
|------|-----------|------------|
| CRUD only | `CrudRepositoryInterface` | `HasCrud` |
| Filter plus get or paginate | `FilterableRepositoryInterface` | `HasFilter` |
| Filter plus order | `FilterableRepositoryInterface`, `OrderableRepositoryInterface` | `HasFilter`, `HasOrder` |
| Query from request | `RequestQueryRepositoryInterface` | `HasFilter`, `HasOrder`, `HasRequestQuery` |
| Full stack | `RepositoryInterface` | `HasCrud`, `HasFilter`, `HasOrder`, `HasRequestQuery` |

## Why this matters

- Interfaces remain segregated.
- Repositories stay explicit about supported behavior.
- Query lifecycle stays centralized in `EloquentRepository`.
- New features can be added as independent behavior slices.
