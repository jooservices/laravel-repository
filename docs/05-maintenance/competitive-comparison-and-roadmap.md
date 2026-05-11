# Competitive Comparison And Roadmap

This page compares JOOservices Laravel Repository with widely used Laravel repository or query packages and proposes a roadmap that fits the current trait-based architecture.

## Packages compared

- `jooservices/laravel-repository`: trait-based repository composition with CRUD, filter, order, and opt-in request-query composition including allowlists, aliases, scope definitions, relation count clauses, derived include helpers, and nested relation filters.
- `spatie/laravel-query-builder`: request-driven query builder for APIs with allowlists and strict query controls.
- `prettus/l5-repository`: broad repository package with criteria, caching, validation, presenters, and generators.
- `bosnadev/repositories`: older repository package included as historical reference only. It is abandoned and should not be used as a feature target by itself.

## Comparison matrix

| Feature | JOOservices Laravel Repository | Spatie Query Builder | Prettus L5 Repository | Direction |
| --- | --- | --- | --- | --- |
| Trait-based opt-in composition | Yes. Core design principle. | No. Query builder package, not repository traits. | No. Base repository inheritance. | Keep as-is |
| Segregated repository contracts | Yes. CRUD, filter, order, request-query are separate contracts. | No. Not a repository package. | Partial. Broader interfaces plus add-on contracts. | Keep as-is |
| Basic CRUD | Yes. | No. Out of scope. | Yes. | Keep as-is |
| Fluent filter chaining | Yes, through `HasFilter`. | Yes, through query builder filters. | Yes. | Keep as-is |
| Ordering | Yes, through `HasOrder`. | Yes, with allowed sorts. | Yes. | Keep as-is |
| Pagination | Yes. Length-aware pagination plus opt-in cursor pagination cover both standard page-based and large ordered result-set flows. | Yes. | Yes. | Implemented |
| Request-driven filtering | Yes. Supports implemented clause families for fields, named filters, where clauses, order, scopes, includes, and nested relation filters. | Yes. Core strength. | Yes, through request criteria. | Implemented |
| Allowed filter or sort allowlists | Yes. Opt-in through `AllowsRequestQueryInterface` and `HasAllowedRequestQuery`. | Yes. Strong support. | Partial through searchable fields. | Implemented |
| Strict invalid-query exceptions | Yes. Strict mode rejects unsupported clause families, invalid array-only clause shapes, and disallowed filters, sorts, includes, fields, scopes, relation filters, and named request filters. | Yes. | Partial. | Implemented |
| Relation includes from request | Yes. Supports allowlists and strict relation-existence checks through `with`. | Yes, with allowlists and nested includes. | Yes. | Implemented |
| Field selection from request | Yes. Opt-in through request `fields` and `allowedFields`. | Yes. | Partial through visible or hidden fields. | Implemented |
| Reusable criteria or specification objects | Yes. Opt-in criteria stack through `CriteriaInterface` and `HasCriteria`. | Partial via custom filters, not repository criteria. | Yes. | Implemented |
| Custom filter classes | Yes. Named request filters can be mapped to dedicated request-filter classes. | Yes. | Partial through criteria. | Implemented |
| Scope filters | Yes. Opt-in through allowlisted request-query scopes with parameter support. | Yes. | Partial through criteria or custom query logic. | Implemented |
| Filter aliases | Yes. Opt-in through request-query metadata without exposing internal column names. | Yes. | Partial through custom repository logic. | Implemented |
| Include aliases plus count or exists helpers | Yes. Relation aliases can drive eager-loading includes and derived `Count` and `Exists` helpers. | Yes. | Partial. | Implemented |
| Aggregate include helpers beyond count or exists | Yes. Metadata-driven `sum`, `avg`, `min`, and `max` helpers can be exposed as public request includes. | Yes. | Partial. | Implemented |
| Relation filters such as `whereHas` | Yes. Opt-in nested relation filters support `whereHas`, `orWhereHas`, `whereDoesntHave`, and `orWhereDoesntHave` with allowlisted relation paths and columns. | Yes. | Yes or partial depending on criteria usage. | Implemented |
| Relation count comparisons | Yes. Request `has` clauses support count comparisons on public relation surfaces. | Yes. | Partial. | Implemented |
| Filter value normalization rules | Yes. Metadata can normalize filter values, named filter values, scope parameters, and relation-filter values. | Partial. | Partial through custom repository logic. | Implemented |
| Callback micro filters | Yes. Named request filters can be mapped to closures for one-off request behavior. | Partial through custom filters. | Partial. | Implemented |
| Advanced operators such as exact, partial, beginsWith, endsWith | Yes. First-class semantic operators for request and `Filter` usage. | Yes. | Partial and broader than current package. | Implemented |
| Cursor pagination | Yes. Opt-in through `HasCursorPagination`, with primary-key fallback ordering. | Works at builder level. | Not a core differentiator. | Implemented |
| Chunk, lazy, cursor, and `lazyById` iteration | Yes. Opt-in through `HasIteration`. | Works at builder level. | Not a core differentiator. | Implemented |
| More terminal operations | Yes. Adds `first`, `firstOrFail`, `exists`, `count`, `chunk`, and `lazy` as opt-in traits. | Works at builder level. | Not a core differentiator. | Implemented |
| Caching | Yes. Opt-in generic cache wrappers through `HasCache`, with explicit repository-managed cache keys. | No. | Yes. | Implemented |
| Validation integration | No. | No. | Yes. | Do not prioritize |
| Presenter or transformation layer | No. | No. | Yes. | Avoid |
| Code generators and bindings | No. | No. | Yes. | Optional only |

## Missing advanced features only

Most of the matrix is now implemented. The table below lists only the advanced features that are still missing and still worth considering.

| Feature | What it is | Why it matters | Recommended priority |
| --- | --- | --- | --- |
| Ignored, default, and nullable filter values | Per-filter value rules such as ignoring placeholders, applying defaults, or treating empty input as `NULL`. | Improves request ergonomics and removes controller cleanup code. | High |
| Optional generators and bindings helpers | Artisan scaffolding for repository classes and bindings. | Developer convenience only. | Low |

## Remaining features we can still add

### 1. Ignored, default, and nullable filter values

**What it is**

Extend the current value-rule metadata beyond normalization into defaults, ignored placeholders, and explicit nullable semantics.

**Why it is needed**

- Normalization is now implemented, but repositories still need to decide common placeholder behavior manually.
- Default values and explicit nullability would make request-driven APIs more predictable.

**How to do it**

- Extend the existing value-rule metadata rather than adding a second rule system.
- Keep the behavior opt-in per public filter or scope surface.
- Document exactly which placeholder and null-handling rules are implemented.

### 2. Optional generators and bindings helpers

**What it is**

Developer tooling to generate repository classes, contracts, and container bindings faster.

**Why it is needed**

- Teams that standardize on repositories often want scaffolding support.
- It improves adoption speed, especially in larger Laravel codebases.
- This is useful tooling, but it does not improve runtime capabilities.

**Why it is optional**

- It is convenience tooling, not core package behavior.
- It adds maintenance surface in commands, stubs, and documentation.
- The package should only add this if repository usage patterns are stable enough to scaffold correctly.

**How to do it**

- Add Artisan commands that generate repository classes from package stubs.
- Keep generated files minimal and trait-based.
- Avoid baking in every optional feature by default.
- Document the generated composition clearly so users still understand what is opt-in.

**How to use it**

Users would run a generator command, receive a repository contract and class skeleton, and then choose only the traits they actually want such as CRUD, filter, order, request-query, criteria, or cursor pagination.

## Broader research signals

Recent comparison against Spatie Query Builder, EloquentFilter, Cerbero Query Filters, and Laravel Filter Query String now leaves a much shorter gap list. Aggregate includes, value normalization, callback micro filters, explicit cache helpers, aliases, scope definitions, relation count comparisons, and derived count or exists includes are now covered in this package.

## Features to avoid or defer

These exist in some older repository packages, but they do not fit the current package direction well.

### Presenter or transformation layers

**What**

Repository-owned response transformation or presenter pipelines.

**Why not now**

- It mixes data access with presentation concerns.
- Laravel resources and transformers already solve this elsewhere.

**How to handle instead**

- Keep transformation outside the repository package.

### Validation inside repositories

**What**

Validation rules or validator classes attached directly to repository create or update operations.

**Why not now**

- Request validation belongs closer to HTTP or application services.
- It would make repository responsibilities broader and less composable.

**How to handle instead**

- Let applications use Form Requests, DTO validation, or service-layer validation.

### Monolithic base repository growth

**What**

Moving every new feature into one always-on base class.

**Why not now**

- That would work against the package's core trait-based design.
- It would make repository capabilities less explicit.

**How to handle instead**

- Add new contracts and traits as separate behavior slices.

## Delivery plan

### Phase 1: Richer nullable and default value rules

Goal: extend the new normalization layer into common ignored and nullable request semantics.

- Add ignored, default, and nullable value rules.
- Keep these rules opt-in per repository.
- Document strict-mode behavior around malformed values.

### Phase 2: Optional ecosystem extras

Goal: add non-core convenience features only if real usage justifies them.

- Evaluate repository generators and bindings helpers.
- Avoid presenter and repository-owned validation features.

## Suggested implementation order

If only one feature is started next, start with ignored, default, and nullable value rules.

That gives the best balance of:

- user-facing value
- competitive parity
- reuse of the new value-normalization foundation
- fit with the current trait-based design

After that, generators and binding helpers are the next optional ecosystem candidate. Presenter layers and repository-owned validation should still stay out of scope.
