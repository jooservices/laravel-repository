# Project Overview

The JOOservices Laravel Repository package provides a trait-based repository toolkit for Laravel applications.

## What the package does

The package provides:

- a base `EloquentRepository` that owns model storage and lazy query creation
- opt-in CRUD behavior through `HasCrud`
- opt-in filtering and terminal collection or pagination through `HasFilter`
- opt-in ordering through `HasOrder`
- opt-in request-to-query composition through `HasRequestQuery`
- reusable `Filter` and `Order` value objects
- a `RequestQueryParser` for the implemented request clause families

## What to keep in mind

- No feature is globally active. Repositories opt into behavior through traits and interfaces.
- `HasFilter` is stateful during a single chain and resets query state after `get()` or `paginate()`.
- `RequestQueryParser` documents only the implemented clause groups.
- The package is intentionally smaller than a full data-access framework and should stay minimal.
