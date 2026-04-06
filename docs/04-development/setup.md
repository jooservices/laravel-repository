# Setup

## Install dependencies

```bash
composer install
```

CaptainHook installs automatically after Composer install or update.

Install `gitleaks` separately and ensure it is available on `PATH` before using the repository hooks. The checked-in `pre-commit` and `pre-push` hooks fail fast when `gitleaks` is missing.

## Useful commands

```bash
composer lint
composer lint:all
composer lint:fix
composer lint:phpstan
composer test
composer test:coverage
composer ci
```
