# Architecture

This legacy page remains as a compatibility entry point.

Use the numbered architecture pages instead:

- [Project Overview](./00-architecture/01-project-overview.md)
- [Repository Structure](./00-architecture/02-repository-structure.md)
- [Data Flow](./00-architecture/03-data-flow.md)

The package architecture remains trait-based, with query lifecycle ownership in `EloquentRepository` and request-clause support owned by `RequestQueryParser`.
