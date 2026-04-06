# Testing

## Test layout

- `tests/Unit`: focused unit behavior for support classes, traits, and repository internals
- `tests/Feature`: package integration flows against the Testbench application container and in-memory database

## Commands

```bash
composer test
composer test:coverage
composer lint:phpstan
```

Coverage artifacts are written under `build/coverage` for CI upload and local inspection.
