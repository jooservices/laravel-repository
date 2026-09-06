# Coding Standards

## Quality stack

- Pint (`per` / PER-CS 3.0) is the formatting authority.
- PHP-CS-Fixer handles narrow cleanup that should stay outside Pint.
- PHPCS handles structural checks.
- PHPStan **max** with Larastan and `phpstan-strict-rules` on `src` and
  `tests/Stubs`.
- PHPMD handles maintainability signals.
- PHPUnit covers behavior and regression testing (Faker for test data).

## Package-specific rules

- Keep `declare(strict_types=1)` enabled.
- Prefer explicit native types.
- Preserve trait-based architecture and interface segregation.
- Document only implemented runtime behavior (Runtime Truth).
- Do not suppress valid static-analysis or style findings.

## Commands

```bash
composer lint
composer test
composer check
composer ci
```
