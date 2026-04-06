---
name: class-purpose-and-module-map
description: "Use when deciding where a change belongs. Maps source modules to their intended responsibilities in jooservices/laravel-repository."
---

# Class Purpose And Module Map

## Repository classes

- `EloquentRepository`: base repository with model storage, `newQuery()`, and lazy `getQuery()`.
- `HasCrud`: CRUD operations against a fresh query.
- `HasFilter`: filter chaining plus terminal collection and pagination operations.
- `HasOrder`: order application over the active query.
- `HasRequestQuery`: request-to-query composition built on filter and order support.

## Support classes

- `Filter`: immutable filter value object that applies itself to a builder.
- `Order`: immutable ordering value object.
- `RequestQueryParser`: parses request payloads into the implemented query clause groups.

## Placement rules

- Query-builder lifecycle changes belong in `EloquentRepository`.
- Clause parsing changes belong in `RequestQueryParser` and related docs/tests.
- New repository behaviors should be considered as separate traits before expanding existing ones.
