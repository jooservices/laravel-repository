# JOOservices Laravel Repository

The JOOservices Laravel Repository package is a PHP 8.5+ Laravel package for trait-based repository composition, CRUD support, filter and order pipelines, and request-driven query assembly.

Package name: `jooservices/laravel-repository`

## Install

```bash
composer require jooservices/laravel-repository
```

Optionally publish the package config:

```bash
php artisan vendor:publish --tag=laravel-repository-config
```

## Quick example

```php
use App\Models\User;
use Jooservices\LaravelRepository\Contracts\RepositoryInterface;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasCrud;
use Jooservices\LaravelRepository\Traits\HasFilter;
use Jooservices\LaravelRepository\Traits\HasOrder;

final class UserRepository extends EloquentRepository implements RepositoryInterface
{
    use HasCrud;
    use HasFilter;
    use HasOrder;

    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}

$repository = app(UserRepository::class);
$user = $repository->find($id);
$users = $repository->filter(['status' => 'active'])->orderBy(['created_at' => 'desc'])->paginate(15);
```

## What is supported today

- trait-based repository composition through segregated contracts and traits
- CRUD operations through `HasCrud`
- filter chains, collection retrieval, and pagination through `HasFilter`
- ordering through `HasOrder`
- request-driven query parsing through `HasRequestQuery`
- reusable `Filter` and `Order` value objects

## Important current boundaries

- repositories opt into behavior through traits; no feature is globally implied
- query state is lazily created and reset after terminal filter operations
- `RequestQueryParser` supports only the implemented clause families

## Documentation

Start with:

- [Documentation Hub](docs/README.md)
- [Installation](docs/01-getting-started/installation.md)
- [Quick Start](docs/01-getting-started/quick-start.md)
- [Trait-Based Composition](docs/02-user-guide/trait-based-composition.md)
- [Competitive Comparison And Roadmap](docs/12-competitive-comparison-and-roadmap.md)
- [Risks, Legacy, and Gaps](docs/11-risks-legacy-and-gaps.md)

## AI Support

This repository includes an AI skill pack for agents working in Cursor, Claude Code, VS Code, JetBrains, and Antigravity.

Start with:

- [AGENTS.md](AGENTS.md)
- [CLAUDE.md](CLAUDE.md)
- [AI Skills Map](ai/skills/README.md)
- [AI Skills Usage Guide](ai/skills/USAGE.md)

The canonical skill source lives in [`.github/skills/`](.github/skills/), with adapter layers for each supported AI environment.

## Development

```bash
composer lint:all
composer test
```

Contributor workflow details live in:

- [Setup](docs/04-development/setup.md)
- [Coding Standards](docs/04-development/coding-standards.md)
- [Testing](docs/04-development/testing.md)
- [CI/CD](docs/04-development/ci-cd.md)
- [Release Process](docs/04-development/release-process.md)
- [AI Skills](docs/04-development/ai-skills.md)

## GitHub Actions and Services

The repository workflow set is designed to include CI, release, PR labeler, semantic PR title, scorecard, and secret-scanning workflows.

The CI baseline covers security checks, linting, tests with coverage artifacts, and optional dependency review. Release is tag-driven through `vX.Y.Z` tags.

Current external service integrations:

- `Codecov` for CI coverage uploads when `CODECOV_TOKEN` is configured
- `Packagist` for release-time package update notifications

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

This project is licensed under the [MIT License](LICENSE).
