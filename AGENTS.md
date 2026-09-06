# jooservices/laravel-repository

This file adds project-only rules.

- PHP `>= 8.5`, Laravel package: `illuminate/*` `^12|^13`, Orchestra Testbench `^10|^11`
- Namespace **must** be `JOOservices\LaravelRepository\` (uppercase `OO`)
- Trait-composed repositories; opt-in capabilities — no kitchen-sink base class
- `EloquentRepository` remains extensible by design; other concrete classes are `final` unless documented otherwise
- Cache wrappers use `Illuminate\Contracts\Cache\Repository` (extends PSR-16) via `Cache\Factory` — no Cache facade
- Pint `per`; PHPStan **max** + Larastan + strict-rules on `src` + `tests/Stubs`; PHPCS + PHPMD; no ignore
- Opt-in presets: `ApiRepository`, `ReadRepository`; Artisan `make:repository`
- Commands: `composer lint`, `composer test`, `composer check`, `composer ci`
