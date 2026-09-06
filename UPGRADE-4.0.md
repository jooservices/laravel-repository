# Upgrade guide — 1.x → 4.0.0

`v4.0.0` is a major line for JOOservices standards alignment, DX presets, and
audit-hardened request-query / cache / cursor behavior. There is no automatic
compatibility shim.

## Breaking or behavior changes

### Read presets

- `ReadRepository` implements `ReadRepositoryInterface` and **does not**
  implement full `RepositoryInterface` CRUD methods.
- If you type-hinted CRUD against a read preset, switch to `ApiRepository` or
  implement CRUD traits explicitly.

### Query profiles

- `forProfile()` no longer resets omitted allowlists to unrestricted `null`.
- Set restrictive defaults **before** `withQueryProfiles()` / `forProfile()`.
- Explicit profile values (including `strict: false` or `includes: null`) still
  broaden access when you intend that.

### Relations in request query

- Prefer native `Relation` (or subclass) return types on Eloquent relation
  methods.
- Untyped methods are accepted only when the path is explicitly allowlisted
  (`allowedIncludes` / relation-filter keys) or listed in `$trustedRelations`.
- Trusting `posts` does **not** authorize untyped `posts.somethingElse`.

### Cache keys

- Supported object parts: `DateTimeInterface`, `Stringable`, `JsonSerializable`.
- Other objects throw `InvalidArgumentException`.
- DateTime keys include microseconds (`Y-m-d\TH:i:s.uP`).

### Named filters

- Filters that use top-level `orWhere` stay nested under criteria.
- Joins, selects, orders, limits, groups, and havings from named filters are
  still applied on the outer query.

### Cursor pagination

- Sparse column lists and existing builder projections merge with order/PK
  columns; primary keys are qualified to avoid ambiguous `id` with joins.
- Aggregate Expression columns such as `withCount` are preserved.

### Tooling

- PHPStan **max** + Larastan + strict-rules; Pint **`per`**.
- Local gate: `composer lint` and `composer test` (or `composer check` / `composer ci`).

## Suggested upgrade steps

1. Run the test suite against `^4.0` in a branch.
2. Add return types to allowlisted relation methods, or set `$trustedRelations`.
3. Review every `forProfile()` call site for baseline vs explicit broaden.
4. Audit `cacheKey()` call sites for unsupported objects.
5. Re-read [Request Query Support](docs/02-user-guide/request-query.md) and the
   [Cookbook](docs/03-examples/cookbook.md).
