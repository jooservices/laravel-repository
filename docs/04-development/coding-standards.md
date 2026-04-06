# Coding Standards

## Quality stack

- Pint is the formatting authority.
- PHP-CS-Fixer handles narrow cleanup that should stay outside Pint.
- PHPCS handles structural checks.
- PHPStan handles static analysis.
- PHPMD handles maintainability signals.
- PHPUnit covers behavior and regression testing.

## Package-specific rules

- Keep strict types enabled.
- Prefer explicit native types.
- Preserve trait-based architecture and interface segregation.
- Document only implemented runtime behavior.

## PHPStan ratchet decision

- The current repository baseline is PHPStan level `6`.
- The ratchet to level `6` required filling current iterable-value and generic-class gaps in contracts, repository internals, parser shapes, and test stubs.
- Future ratchets beyond level `6` should be treated as focused code-quality tasks rather than simple configuration flips.
