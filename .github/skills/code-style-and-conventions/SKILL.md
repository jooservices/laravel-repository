---
name: code-style-and-conventions
description: "Use when editing PHP, docs, or repository config. Captures the repository's command map, formatter authority, naming expectations, and doc style."
---

# Code Style And Conventions

## Quality stack

- Formatting authority: `Pint`
- Narrow cleanup: `PHP-CS-Fixer`
- Structural checks: `PHPCS`
- Static analysis: `PHPStan`
- Maintainability: `PHPMD`
- Tests: `PHPUnit`

## Style rules

- Keep `declare(strict_types=1);` in PHP files.
- Prefer explicit types and precise PHPDoc only where native types are insufficient.
- Preserve existing namespaces under `Jooservices\\LaravelRepository\\`.
- Keep comments sparse and only for non-obvious behavior.

## Repository docs style

- Use `JOOservices Laravel Repository` as the product name.
- Use `jooservices/laravel-repository` only for the Composer package identifier.
- Keep examples aligned with current traits, contracts, and request-query capabilities.
