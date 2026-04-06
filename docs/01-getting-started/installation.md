# Installation

## Requirements

- PHP ^8.5
- Laravel ^12.0
- `illuminate/contracts`, `illuminate/database`, `illuminate/http`, `illuminate/support` ^12.0

## Install with Composer

```bash
composer require jooservices/laravel-repository
```

The package uses Laravel package discovery for the service provider.

## Optional config publish

```bash
php artisan vendor:publish --tag=laravel-repository-config
```

Published config options:

- `default_per_page`
- `request_key`
