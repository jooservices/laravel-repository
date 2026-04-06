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
- `RequestQueryParser` documents only implemented clause families.
- Repositories opt into behavior through interfaces and traits; no feature is globally implied.
