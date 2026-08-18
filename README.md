# Bhitti

**Bhitti** is a lightweight, performance-first PHP framework built around three priorities: **Performance**, **Simplicity**, and **Efficiency**.

Bhitti is designed for modern PHP applications running with PHP-FPM while keeping the application layer small, explicit, and easy to understand. The framework provides routing, middleware, database access, migrations, sessions, cache, rate limiting, validation, authentication helpers, and a lightweight service container without introducing a large full-stack architecture.

> This repository contains the **Bhitti starter application**. Framework core code lives in [`sayedsahin/bhitti-framework`](https://github.com/sayedsahin/bhitti-framework), and full developer documentation lives in [`sayedsahin/bhitti-doc`](https://github.com/sayedsahin/bhitti-doc).

## Requirements

- PHP **8.3 or newer**
- Composer
- PDO
- Mbstring
- A PDO driver for the database you use (`pdo_mysql`, `pdo_pgsql`, or `pdo_sqlite`)

Optional extensions:

- PhpRedis for Redis cache, sessions, and rate limiting
- Memcached for Memcached cache, sessions, and rate limiting
- APCu for APCu cache and rate limiting
- GD when application image utilities require it

## Installation

The Bhitti application installs the framework through Composer. The application package requires:

```json
{
    "require": {
        "php": ">=8.3",
        "sayedsahin/bhitti-framework": "^0.2"
    }
}
```

For a normal application or production deployment, clone only the Bhitti application and install its Composer dependencies:

```bash
git clone https://github.com/sayedsahin/bhitti.git
cd bhitti
composer install
cp .env.example .env
```

Composer installs `sayedsahin/bhitti-framework` as the framework dependency. A separate local framework checkout is not required for normal application development or production deployment.

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Configure `.env`:

```dotenv
APP_DEBUG=true
BASE_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=bhitti
DB_USERNAME=root
DB_PASSWORD=

SESSION_ENABLED=true
SESSION_DRIVER=native
SESSION_LIFETIME=7200

CACHE_DRIVER=file
RATE_LIMIT_STORE=file
```

Run migrations:

```bash
php run migrate
```

Optional development seed data:

```bash
php run db:seed
```

Review the seed files in `database/seeders/` before running them outside development.

Start the local development server:

```bash
php -S 127.0.0.1:8000 -t public
```

Open:

```text
http://127.0.0.1:8000
```

## Request Lifecycle

Bhitti keeps early request processing small and defers session-aware work until a route is found:

```text
Request
  ↓
Kernel middleware (stateless)
  ↓
Route matching
  ├─ 404 / 405 → Response
  └─ FOUND
       ↓
     Web session configuration
       ↓
     Route-level global middleware
       ↓
     Route-specific middleware
       ↓
     Controller class middleware attributes
       ↓
     Controller method middleware attributes
       ↓
     Controller
       ↓
     Response
```

Kernel middleware should remain stateless. Session-dependent middleware belongs to the route level.

## Routing

Routes are defined in `config/routes.php`.

```php
use App\Controllers\UserController;
use App\Middlewares\Authenticated;

$route->get('/users', [UserController::class, 'index']);

$route->get('/users/{id:\\d+}', [
    UserController::class,
    'show',
    [Authenticated::class],
]);
```

API routes can be grouped normally:

```php
$route->addGroup('/api', function () use ($route) {
    $route->get('/users', [UserController::class, 'apiIndex']);
});
```

## Controllers

Controllers live in `app/Controllers/` and are resolved through the service container after middleware succeeds.

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

final class UserController extends Controller
{
    public function index(): string
    {
        $users = db()
            ->table('users')
            ->select('id', 'name', 'email')
            ->get();

        return view('users.index', [
            'users' => $users,
        ]);
    }
}
```

Concrete constructor dependencies are autowired by the container.

## Controller Middleware Attributes

Bhitti supports repeatable PHP middleware attributes on controller classes and methods.

```php
use App\Middlewares\RoleMiddleware;
use Bhitti\Http\Middleware\Attributes\Middleware;

#[Middleware(RoleMiddleware::class, ['user'])]
final class ProfileController extends Controller
{
    // Every routed action receives RoleMiddleware.
}
```

Method-level middleware applies only to that action:

```php
use App\Middlewares\Guest;
use Bhitti\Http\Middleware\Attributes\Middleware;

#[Middleware(Guest::class)]
public function registrationProcess()
{
    // ...
}
```

Attributes are repeatable:

```php
#[Middleware(Authenticated::class)]
#[Middleware(RoleMiddleware::class, ['admin'])]
public function dashboard(): string
{
    return view('admin.dashboard');
}
```

Controller middleware metadata is collected during route registration and stored with the prepared route handler. After changing controller middleware attributes, rebuild the route cache:

```bash
php run route:cache
```

## Middleware Configuration

Global middleware is separated into kernel-level and route-level stacks in `config/middleware.php`:

```php
return [
    'kernel' => [
        'web' => [WebHeaders::class],
        'api' => [ApiHeaders::class],
    ],

    'route' => [
        'web' => [
            RateLimit::class,
            RememberMe::class,
            Csrf::class,
        ],
        'api' => [
            RateLimit::class,
        ],
    ],
];
```

Middleware returns `null` to continue or a `Response` to stop the request.

## Views

Views are plain PHP files stored in:

```text
resources/views/
```

Bhitti uses the `.view.php` extension.

```php
return view('auth.login', [
    'title' => 'Login',
]);
```

This loads:

```text
resources/views/auth/login.view.php
```

`view()` returns the rendered string, so controllers should normally return it.

Layouts and sections are supported by the view layer. Escape dynamic output with `e()` and keep raw HTML developer-controlled.

## Database and Query Builder

Bhitti uses PDO and supports named MySQL, PostgreSQL, and SQLite connections.

### `db()` helper

```php
$users = db()
    ->table('users')
    ->where('status', 'active')
    ->order('id DESC')
    ->limit(20)
    ->get();
```

Named connection:

```php
$users = db('pgsql')
    ->table('users')
    ->get();
```

### Global `DB::query()` model

```php
use Bhitti\Database\DB;

$users = DB::query()
    ->table('users')
    ->select('id', 'name', 'email')
    ->get();
```

Use another configured connection when needed:

```php
$users = DB::query('sqlite')
    ->table('users')
    ->get();
```

### Application models

Application models are lightweight `QueryBuilder` subclasses:

```php
use Bhitti\Database\QueryBuilder;

final class User extends QueryBuilder
{
    protected string $defaultTable = 'users';
    protected array $defaultSelect = ['id', 'name', 'email'];
}
```

```php
$users = User::query()
    ->where('status', 'active')
    ->get();
```

Query Builder values are bound through PDO. Normal table/column/operator APIs validate identifiers and supported grammar. Use `selectRaw()` or `raw()` only for developer-controlled SQL expressions.

Builder-generated `UPDATE` and `DELETE` operations require a `WHERE` condition.

## Migrations

Create a table migration:

```bash
php run migrate:create users
```

Create an alteration migration:

```bash
php run migrate:alter users
```

Run migrations:

```bash
php run migrate
```

Rollback:

```bash
php run migrate:rollback
php run migrate:rollback --step=2
```

Status:

```bash
php run migrate:status
```

Migration records store the migration name, batch, and execution time. Bhitti does not use migration file checksums.

## Database Seeders

Create and automatically register a seeder:

```bash
php run create:seeder users
```

Run active seeders from `database/seeders/database.seeder.php`:

```bash
php run db:seed
```

Run a single seeder:

```bash
php run db:seed --filename=users
```

Seeders are plain PHP files returning closures and execute in registry order.

## Sessions, Cache, and Rate Limiting

Available session drivers:

```text
native
redis
memcached
null
```

Available cache drivers:

```text
array
file
apcu
redis
memcached
```

Available rate-limit stores:

```text
file
apcu
redis
memcached
```

Redis connections are configured as named profiles under `config/database.php`. Cache, session, and rate limiting can share the same profile or use separate profiles when isolation is required.

Memcached connection setup is also centralized under `config/database.php`.

## Trusted Proxies

Trusted proxies are disabled unless configured.

Exact IPv4/IPv6 addresses and IPv4/IPv6 CIDR ranges are supported:

```dotenv
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8,172.16.0.0/12
```

Forwarded headers are trusted only when the direct proxy address is configured as trusted.

## Redirects

Normal redirects are intended for local application URLs:

```php
return response()->redirect()->to('/dashboard');
```

Use `away()` explicitly for an external URL:

```php
return response()->redirect()->away('https://example.com');
```

## Command-Line Tools

Bhitti's CLI entry point is the root `run` script:

```bash
php run
```

Main framework commands:

```bash
php run migrate:create users
php run migrate:alter users
php run migrate
php run migrate:rollback
php run migrate:status

php run create:seeder users
php run db:seed
php run db:seed --filename=users

php run config:cache
php run route:cache
php run cache:clear
```

Application-specific commands can be registered in `config/commands.php`.

## Directory Structure

```text
bhitti/
├── app/
│   ├── Controllers/
│   ├── Helpers/
│   ├── Middlewares/
│   ├── Models/
│   └── Supports/
├── bootstrap/
│   ├── app.php
│   └── services.php
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── assets/
│   └── index.php
├── resources/
│   └── views/
├── storage/
│   └── cache/
├── .env.example
├── composer.json
└── run
```

Framework internals are not copied into the application; they are provided by `sayedsahin/bhitti-framework` through Composer.

## Production

Use the deployment environment chosen for the application and point the web document root to:

```text
public/
```

Typical production preparation:

```bash
composer install --no-dev --optimize-autoloader
php run config:cache
php run route:cache
```

Production environment should normally include:

```dotenv
APP_DEBUG=false
SESSION_SECURE=true
```

When debug mode is disabled, production migration and rollback commands require explicit `--force`:

```bash
php run migrate --force
php run migrate:rollback --force
```

Keep `.env`, credentials, and runtime cache files out of source control. Redis and Memcached endpoints should remain private application infrastructure.

## Documentation

Full developer documentation:

- **Documentation:** https://github.com/sayedsahin/bhitti-doc
- **Starter application:** https://github.com/sayedsahin/bhitti
- **Framework core:** https://github.com/sayedsahin/bhitti-framework

The documentation covers installation, request lifecycle, configuration, routing, controller middleware attributes, views, Query Builder, models, migrations, seeders, sessions, cache, rate limiting, security, deployment, performance, and upgrading from `v0.1.0`.

## Project Principle

> Provide the features most applications need while keeping the framework fast, simple, explicit, and understandable.
