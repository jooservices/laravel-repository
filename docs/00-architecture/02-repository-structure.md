# Repository Structure

## Source layout

```text
src/
├── Contracts/
├── Exceptions/
├── Repositories/
├── Support/
└── Traits/
```

## Ownership map

- `Contracts/`: public repository capability surfaces
- `Repositories/EloquentRepository.php`: model ownership and lazy query lifecycle
- `Traits/`: composable behavior slices for CRUD, filtering, ordering, and request-query support
- `Support/`: immutable value objects and request parsing helpers
- `Exceptions/`: package-specific exception types

## Design posture

The repository should evolve through small, composable modules. New behaviors should be considered as separate traits or support classes before expanding the base repository.
