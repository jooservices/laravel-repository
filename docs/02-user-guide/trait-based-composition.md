# Trait-Based Composition

Use only the traits your repository actually needs.

| Need | Implement | Use traits |
|------|-----------|------------|
| CRUD only | `CrudRepositoryInterface` | `HasCrud` |
| Filter plus get or paginate | `FilterableRepositoryInterface` | `HasFilter` |
| Filter plus read terminals | `FilterableRepositoryInterface`, `ReadableRepositoryInterface` | `HasFilter`, `HasRead` |
| Filter plus reusable criteria | `FilterableRepositoryInterface`, `CriteriaRepositoryInterface` | `HasCriteria`, `HasFilter` |
| Filter plus cursor pagination | `FilterableRepositoryInterface`, `CursorPaginateableRepositoryInterface` | `HasFilter`, `HasCursorPagination` |
| Filter plus chunk, lazy, cursor, or `lazyById` iteration | `FilterableRepositoryInterface`, `IteratesRepositoryInterface` | `HasFilter`, `HasIteration` |
| Filter plus explicit cache helpers | `CacheableRepositoryInterface` | `HasCache` |
| Filter plus order | `FilterableRepositoryInterface`, `OrderableRepositoryInterface` | `HasFilter`, `HasOrder` |
| Query from request | `RequestQueryRepositoryInterface` | `HasFilter`, `HasOrder`, `HasRequestQuery` |
| Restricted query from request | `AllowsRequestQueryInterface`, `RequestQueryRepositoryInterface` | `HasAllowedRequestQuery`, `HasFilter`, `HasOrder`, `HasRequestQuery` |
| Named request filters or callback micro filters | `ProvidesRequestFiltersInterface`, `RequestQueryRepositoryInterface` | `HasRequestFilters`, `HasFilter`, `HasOrder`, `HasRequestQuery` |
| Full stack | `RepositoryInterface` | `HasCrud`, `HasFilter`, `HasOrder`, `HasRequestQuery` |

## Why this matters

- Interfaces remain segregated.
- Repositories stay explicit about supported behavior.
- Query lifecycle stays centralized in `EloquentRepository`.
- New features can be added as independent behavior slices.
- Criteria are reapplied on each fresh repository query rather than forcing a monolithic base repository.
- Read terminals reset the active query just like the existing filter terminal operations.
- Cache keys remain explicit because caching is opt-in and separate from the core query lifecycle.
