---
name: architecture-and-design-principles
description: "Use when changing source code or docs that describe package behavior. Covers trait-based composition, interface segregation, and module boundaries."
---

# Architecture And Design Principles

## Primary principles

- Trait-based composition over inheritance-heavy repositories.
- Interface segregation over one oversized contract.
- Minimal abstractions that fit current package scale.
- Query lifecycle remains centralized in `EloquentRepository`.

## Module ownership

- `src/Contracts`: repository contracts and behavior surfaces.
- `src/Repositories/EloquentRepository.php`: model storage and lazy query builder lifecycle.
- `src/Traits`: CRUD, filter, order, and request-query behavior slices.
- `src/Support`: value objects and request parsing helpers.
- `src/Exceptions`: package-specific exceptions.

## Guardrails

- Do not merge behavior traits into a single kitchen-sink implementation.
- Do not document behavior that depends on a trait unless the trait is explicitly part of the example or contract.
- Keep query reset semantics explicit whenever terminal filter operations are involved.
- Keep criteria application idempotent per active builder.
- Keep request-query safety in allowlists, parser/support classes, and package exceptions rather than ad hoc controller examples.
