<p align="center">
<img src="https://res.cloudinary.com/dudxt4lp6/image/upload/v1717408304/orion-for-laravel-logo_uqyyz3.png" width="400">
</p>

<p align="center">
<a href="https://packagist.org/packages/tailflow/laravel-orion"><img src="https://img.shields.io/packagist/v/tailflow/laravel-orion.svg" alt="Latest Version on Packagist"></a>
<a href="https://github.com/tailflow/laravel-orion/actions"><img src="https://img.shields.io/github/actions/workflow/status/tailflow/laravel-orion/ci.yml?branch=main" alt="Build Status"></a>
</p>

## Introduction

Orion for Laravel allows you to build a fully featured REST API based on your Eloquent models and relationships with simplicity of Laravel as you love it.

## Requirements

- PHP `>= 7.3`
- Laravel / Illuminate components `>= 8.0`
- PHP JSON extension
- Doctrine DBAL `^2.9`, `^3.1`, or `^4.0`
- Symfony YAML `^5.3`, `^6.0`, or `^7.0`

Optional requirements depend on the features you enable:

- Database-backed stored search links require a published migration and a configured database connection.
- Redis-backed stored search links require a Laravel cache store that uses Redis.
- Filesystem-backed stored search links require a writable storage directory.

## Installation

Install the package with Composer:

```bash
composer require tailflow/laravel-orion
```

Publish the configuration file when you need to customize Orion behavior:

```bash
php artisan vendor:publish --tag=orion-config
```

## Documentation

Documentation can be found on the [website](https://orion.tailflow.org).

## Features

Orion generates REST API endpoints from your Eloquent models and controller configuration.

Core features include:

- Standard resource operations: index, show, store, update, delete, and restore.
- Relationship resources for common Eloquent relations.
- Search endpoints with filters, nested filters, scopes, sorting, includes, aggregates, and text search.
- Batch create, update, delete, and restore operations.
- Authorization through Laravel policies.
- Request, resource, and collection resource resolution.
- Optional route discovery for Orion controllers.
- OpenAPI specification generation.
- Stored search links for sharing short URLs for complex searches.

## Stored Search Links

Stored search links let a client submit a complex search payload once, receive a short id, and replay that search with a short URL.

Create a stored search link:

```http
POST /api/v1/posts/search-links
Content-Type: application/json
Accept: application/json
```

Example body:

```json
{
    "filters": [
        { "field": "title", "operator": "like", "value": "%Laravel%" }
    ],
    "includes": [
        { "relation": "user" }
    ],
    "sort": [
        { "field": "title", "direction": "asc" }
    ],
    "scopes": [
        { "name": "published" }
    ]
}
```

Example response:

```json
{
    "id": "srch_8f3Kx92",
    "url": "/api/v1/posts/search?sid=srch_8f3Kx92"
}
```

Replay the stored search:

```http
GET /api/v1/posts/search?sid=srch_8f3Kx92
Accept: application/json
```

The stored payload is validated with the same Orion whitelist rules used by normal search requests. Stored links are scoped to the registered route context, so a link created for one resource route cannot be replayed against another resource route.

The following payload keys are stored by default:

- `aggregates`
- `filters`
- `includes`
- `limit`
- `scopes`
- `search`
- `sort`

The following query-string keys are stored by default:

- `include`
- `only_trashed`
- `with_avg`
- `with_count`
- `with_exists`
- `with_max`
- `with_min`
- `with_sum`
- `with_trashed`

## Stored Search Link Configuration

Stored search links are configured in `config/orion.php` under `search_links`.

```php
'search_links' => [
    'driver' => env('ORION_SEARCH_LINKS_DRIVER', 'filesystem'),
    'id_prefix' => 'srch_',
    'id_length' => 12,
    'ttl' => env('ORION_SEARCH_LINKS_TTL', 86400),
],
```

Set the driver with:

```env
ORION_SEARCH_LINKS_DRIVER=filesystem
ORION_SEARCH_LINKS_TTL=86400
```

`ttl` is expressed in seconds. Set it to `null` in config if stored links should not expire.

### Filesystem Driver

The filesystem driver stores each search link as a JSON file. It is the default driver.

```php
'search_links' => [
    'driver' => 'filesystem',
    'filesystem' => [
        'path' => storage_path('framework/orion/search-links'),
    ],
],
```

Requirements:

- The configured path must be writable by the PHP process.
- This driver is best for local development or single-server deployments.

### Database Driver

The database driver stores search links in a database table.

```php
'search_links' => [
    'driver' => 'database',
    'database' => [
        'connection' => null,
        'table' => 'orion_search_links',
    ],
],
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag=orion-search-links-migration
php artisan migrate
```

Requirements:

- A configured database connection.
- The `orion_search_links` table, or a custom table matching the configured table name.

This driver is a good default for multi-server applications where all app servers share the same database.

### Redis Driver

The Redis driver stores search links through Laravel's cache repository using a Redis-backed cache store.

```php
'search_links' => [
    'driver' => 'redis',
    'redis' => [
        'cache_store' => 'redis',
        'key_prefix' => 'orion:search-links:',
    ],
],
```

Requirements:

- A configured Laravel cache store named `redis`, or another Redis-backed store configured in `cache_store`.
- Redis connectivity from the application.

This driver is best for short-lived links and high-read internal systems.

## Testing

Run the test suite with:

```bash
vendor/bin/phpunit
```

To run only the stored search link tests:

```bash
vendor/bin/phpunit tests/Feature/StoredSearchLinkOperationsTest.php
```

## Supported By

<a href="https://geecko.com?utm_campaign=opensource&utm_source=laravel-orion&utm_medium=github" target="_blank">
<img src="https://res.cloudinary.com/dudxt4lp6/image/upload/v1639908579/Laravel%20Orion/logo_geecko_hcuz34.svg" width="300">
</a>

<a href="https://laraveldaily.com?utm_campaign=opensource&utm_source=laravel-orion&utm_medium=github" style="margin-left: 1rem">
<img src="https://res.cloudinary.com/dudxt4lp6/image/upload/v1667408230/Laravel%20Orion/logo_laraveldaily_p3d00p.png" height="100">
</a>

## License

The Laravel Orion is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
