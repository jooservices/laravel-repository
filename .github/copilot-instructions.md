# Copilot Instructions

Apply `AGENTS.md` as the repository-wide baseline.

For non-trivial work in this repository:

1. Read `AGENTS.md`.
2. Load the relevant canonical skills from `.github/skills/`.
3. Keep changes minimal and module-appropriate.
4. Update docs and tests in the same change when behavior or contributor workflow changes.
5. Use only the documented composer commands from `AGENTS.md` and `composer.json`.

Repository truth to preserve:

- `EloquentRepository` owns the model and lazy query lifecycle.
- Traits own behavior slices and remain intentionally composable.
- `RequestQueryParser` documents only implemented clause families, including the current request-query metadata and relation-count surfaces.
- Repositories opt into behavior through interfaces and traits; no feature is globally implied.

Current request-query surfaces to keep truthful in docs and examples:

- fields and named request filters
- callback micro filters
- allowlists and strict mode
- filter aliases and relation aliases
- scope filters and scope definitions
- relation count clauses
- eager-load includes plus derived `Count` and `Exists` helpers and metadata-defined aggregate helpers
- value-normalization rules for filters, named filters, scopes, and relation filters
- nested relation filters such as `whereHas`, `orWhereHas`, `whereDoesntHave`, and `orWhereDoesntHave`
