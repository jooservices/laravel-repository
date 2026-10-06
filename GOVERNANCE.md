# Governance

## Project model

`jooservices/laravel-repository` is maintained by JOOservices as an owner-driven project. The project owner holds final decision authority.

## Roles

| Role | Holder | Responsibility |
| --- | --- | --- |
| Owner / lead maintainer | Viet Vu (JOOservices) | Roadmap, API and architecture decisions, release approval, access control, and final arbitration |
| Maintainers | Appointed by the owner | Review pull requests, maintain the quality gates, and handle conduct reports |
| Contributors | Everyone else | Propose changes through issues and pull requests following [CONTRIBUTING.md](CONTRIBUTING.md) |

## Decision making

- Day-to-day changes are reviewed and merged through pull requests following [CONTRIBUTING.md](CONTRIBUTING.md).
- API design changes and scope additions are decided by the owner after discussion in an issue or pull request.
- **Releases require explicit owner approval.** Do not create a release tag, publish the package, or create a GitHub Release without it.
- Breaking API changes are reserved for major versions, consistent with the project's [Semantic Versioning](CHANGELOG.md) policy.

## Quality authority

The package quality gates include Pint with the `per` preset, PHPCS, PHPStan at max level with Larastan and strict rules, PHPMD, PHP-CS-Fixer, and the Composer quality scripts. CI enforces a 90% statement-coverage threshold. Any proposed gate change must be reviewed by the owner and comply with the JOOservices workspace policy; an exception must be documented under that policy.

## Conduct enforcement

Report Code of Conduct concerns to [admin@jooservices.com](mailto:admin@jooservices.com). Maintainers handle reports under the [Code of Conduct](CODE_OF_CONDUCT.md).

## Changes to this document

Changes require owner approval through a pull request.
