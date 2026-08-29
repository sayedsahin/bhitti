# Bhitti

**Bhitti** is a lightweight, performance-first PHP framework designed around three core principles: **Performance, Simplicity, and Efficiency**.

Bhitti provides the essential application infrastructure needed to build modern PHP applications while keeping the framework small, explicit, and easy to understand.

## Why Bhitti?

Bhitti is designed for developers who want more structure than a minimal router, but less complexity than a large full-stack framework.

Its design focuses on:

- Low framework overhead
- PHP-FPM-oriented performance
- Simple and explicit architecture
- Minimal unnecessary abstraction
- Fast application bootstrap
- Route and configuration caching
- Lazy session initialization
- Reusable Redis and Memcached connections
- PDO-based database access
- Broad deployment compatibility

The goal is straightforward: provide the features most applications need without making the framework difficult to understand or maintain.

## Built-in Features

### Framework Built-ins

Bhitti Framework includes:

- Fast routing with route parameters, groups, and route caching
- Route-level middleware
- PHP Attribute-based controller class and method middleware
- Lightweight dependency injection container
- Request, response, JSON, and redirect handling
- Native PHP template system
- Optional Twig template support
- Validation
- PDO-based database layer
- Simple, clean and lightweight Query Builder
- Multiple database connections
- Database transactions
- Database migrations
- Database seeders
- Native, Redis, Memcached, and Null session drivers
- Array, File, APCu, Redis, and Memcached cache drivers
- File, APCu, Redis, and Memcached rate limiting
- Redis and Memcached connection management
- Configuration caching
- Route caching
- Centralized exception handling
- Trusted proxy support with IPv4, IPv6, and CIDR matching
- CSRF-ready web request flow
- CLI command infrastructure

### Starter App Includes

The Bhitti application repository provides ready application structure and examples for:

- Authentication
- Remember-me authentication
- Bearer token API authentication
- Role-based authorization
- Login and registration
- Web and API middleware configuration
- Application helpers
- Example migrations and seeders
- Ready project structure for controllers, models, middleware, views, and configuration

Framework features and application-level starter code are intentionally kept separate.

## Requirements

- PHP 8.3 or newer
- Composer
- PDO
- A PDO driver for your database

Optional extensions depend on the features you use:

- PhpRedis
- Memcached
- APCu

## Installation

Clone the Bhitti application:

```bash
git clone https://github.com/sayedsahin/bhitti.git
cd bhitti
```

Install dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Configure the application:

```dotenv
APP_DEBUG=true
BASE_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
DB_NAME=bhitti

SESSION_ENABLED=true
SESSION_DRIVER=native
SESSION_LIFETIME=7200

CACHE_DRIVER=file
RATE_LIMIT_STORE=file
```

After changing environment or configuration values:

```bash
php run config:cache
```

## Quick Start

Run the local development server:

```bash
php -S 127.0.0.1:8000 -t public
```

Open:

```text
http://127.0.0.1:8000
```

Create a simple route in `config/routes.php`:

```php
use App\Controllers\WelcomeController;

$route->get('/', [
    WelcomeController::class,
    'index',
]);
```

Controller:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

final class WelcomeController extends Controller
{
    public function index(): string
    {
        return response()->view('welcome', [
            'title' => 'Bhitti',
        ]);
    }
}
```

View:

```text
resources/views/welcome.view.php
```

Example:

```php
<h1><?= $this->e($title) ?></h1>
```

Request flow:

```text
Request
→ Kernel Middleware
→ Route Match
→ Session Configuration for matched web route
→ Route Middleware
→ Controller Middleware
→ Controller
→ Response
```

## Core Usage

### Application Structure

```text
bhitti/
├── app/
│   ├── Controllers/
│   ├── Helpers/
│   ├── Middlewares/
│   ├── Models/
│   ├── Supports/
│   └── Validation/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   └── views/
├── storage/
├── composer.json
├── run
└── .env
```

Views are stored in:

```text
resources/views/
```

and use the `.view.php` extension.

### Routing

Routes are defined in `config/routes.php`.

```php
use App\Controllers\UserController;

$route->get('/users', [
    UserController::class,
    'index',
]);

$route->post('/users', [
    UserController::class,
    'store',
]);
```

Route parameters:

```php
$route->get('/users/{id:\d+}', [
    UserController::class,
    'show',
]);
```

Route middleware:

```php
$route->get('/dashboard', [
    DashboardController::class,
    'index',
    [
        Authenticated::class,
    ],
]);
```

Middleware with arguments:

```php
$route->get('/admin', [
    AdminController::class,
    'index',
    [
        [RoleMiddleware::class, ['admin']],
    ],
]);
```

### Controller Middleware Attributes

Bhitti supports middleware directly on controller classes and methods using PHP Attributes.

Class-level middleware:

```php
use Bhitti\Http\Middleware\Attributes\Middleware;

#[Middleware(RoleMiddleware::class, ['user'])]
final class ProfileController extends Controller
{
}
```

Method-level middleware:

```php
#[Middleware(Guest::class)]
public function registrationProcess(): Response
{
    // ...
}
```

Attributes are repeatable and support middleware arguments.

Execution order:

```text
route global middleware
→ route-specific middleware
→ controller class middleware
→ controller method middleware
```

Controller middleware attributes are collected during route registration and stored with the route cache.

After changing controller middleware attributes:

```bash
php run route:cache
```

### Middleware Lifecycle

Global middleware is separated into `kernel` and `route` layers:

```php
return [
    'kernel' => [
        'web' => [
            WebHeaders::class,
        ],

        'api' => [
            ApiHeaders::class,
        ],
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

Kernel middleware runs before route dispatch and should remain session-independent.

Route middleware runs only after a route is matched.

For web routes, the session system is configured after a route is found. API requests remain session-free unless the application explicitly changes that behavior.

### Controllers

Controllers are resolved through the dependency injection container.

```php
final class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'users' => $users,
        ]);
    }
}
```

Constructor dependencies are resolved automatically:

```php
final class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports
    ) {
    }
}
```

### Views

Render a view:

```php
return response()->view('users.index', [
    'title' => 'Users',
    'users' => $users,
]);
```

This loads:

```text
resources/views/users/index.view.php
```

Escape dynamic output:

```php
<?= $this->e($title) ?>
```

### Database

Bhitti provides three common query entry points.

#### Database helper

```php
$users = db()
    ->table('users')
    ->where('status', 'active')
    ->get();
```

Named connection:

```php
$users = db('reporting')
    ->table('users')
    ->get();
```

#### Global DB model

```php
use Bhitti\Database\DB;

$users = DB::query()
    ->table('users')
    ->where('status', 'active')
    ->get();
```

#### Application model

```php
final class User extends QueryBuilder
{
    protected string $table = 'users';
}
```

```php
$users = User::query()
    ->where('status', 'active')
    ->get();
```

Each query starts with fresh Query Builder state.

### Query Builder

```php
$users = DB::query()
    ->table('users')
    ->select('id', 'name', 'email')
    ->where('status', 'active')
    ->order('created_at DESC')
    ->limit(20)
    ->get();
```

Raw select expressions must be explicit:

```php
$result = DB::query()
    ->table('orders')
    ->selectRaw('COUNT(id) AS total')
    ->first();
```

Values are bound through PDO.

Builder-generated identifiers, operators, booleans, joins, and limits are validated.

Raw SQL remains an explicit developer-controlled escape hatch:

```php
$users = DB::query()
    ->raw(
        'SELECT * FROM users WHERE status = ?',
        ['active']
    )
    ->get();
```

Builder-generated `UPDATE` and `DELETE` operations require a `WHERE` condition.

### Migrations

Create a migration:

```bash
php run migrate:create users
```

Create an alter migration:

```bash
php run migrate:alter users
```

Run pending migrations:

```bash
php run migrate
```

Show migration status:

```bash
php run migrate:status
```

Rollback:

```bash
php run migrate:rollback
```

Migration history stores:

```text
migration
batch
executed_at
```

Bhitti does not use migration checksums.

### Database Seeders

Create and register a seeder:

```bash
php run create:seeder users
```

Run registered seeders:

```bash
php run db:seed
```

Run one seeder explicitly:

```bash
php run db:seed --filename=users
```

Seeder files are stored in:

```text
database/seeders/
```

### Sessions

Available session drivers:

```text
native
redis
memcached
null
```

Usage:

```php
use Bhitti\Session\Session;

Session::set('user_id', 5);

$userId = Session::get('user_id');

Session::forget('user_id');
```

Sessions can be disabled:

```dotenv
SESSION_ENABLED=false
```

When disabled, Bhitti uses the Null session driver.

Redis and Memcached session backends include concurrency locking safeguards.

### Cache

Available cache drivers:

```text
array
file
apcu
redis
memcached
```

Usage:

```php
cache()->put('user:5', $user, 300);

$user = cache()->get('user:5');

cache()->forget('user:5');
```

Remember a value:

```php
$user = cache()->remember(
    'user:' . $id,
    300,
    fn () => User::query()->find($id)
);
```

`remember()` intentionally remains lightweight and does not add automatic cache-stampede locking.

### Rate Limiting

Available rate-limit stores:

```text
file
apcu
redis
memcached
```

Rate limiting may be applied globally or to individual routes through middleware.

### Redis and Memcached

Redis and Memcached connections are centrally managed by the framework.

Redis supports named connection profiles and connection reuse.

Memcached configuration is centralized so cache, session, and rate limiting use consistent server settings and options.

### Trusted Proxies

Trusted proxy configuration is optional.

```dotenv
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8,172.16.0.0/12
```

Supported matching:

```text
Exact IPv4
Exact IPv6
IPv4 CIDR
IPv6 CIDR
```

Forwarded headers are trusted only when the immediate remote address matches a configured trusted proxy.

### Redirects

Local redirects:

```php
return redirect()->to('/dashboard');
```

`to()` accepts local paths only.

External redirects are explicit:

```php
return redirect()->away('https://example.com');
```

### Authentication and Roles

The starter application includes authentication helpers and middleware.

```php
Auth::login($userId);

Auth::check();
Auth::id();
Auth::user();

Auth::logout();
```

Role checks:

```php
Role::has('admin');

Role::any(['admin', 'editor']);

Role::all(['admin', 'verified']);
```

API routes may use bearer-token authentication.

### Configuration

Read configuration:

```php
$name = config('app.name');
$debug = config('app.debug', false);
```

Build configuration cache:

```bash
php run config:cache
```

Build route cache:

```bash
php run route:cache
```

Clear application cache:

```bash
php run cache:clear
```

### CLI Commands

Framework commands include:

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

Application-specific commands may also be registered through `config/commands.php`.

### Local Framework Development

Normal applications use the released framework package through Composer.

When developing `bhitti-framework` itself locally, you may temporarily use a Composer path repository and symlink workflow so changes in a sibling framework checkout are reflected immediately.

This is a development workflow only and is not the production dependency model.

## Production

Typical production preparation:

```bash
composer install --no-dev --optimize-autoloader
php run config:cache
php run route:cache
```

Recommended production environment:

```dotenv
APP_DEBUG=false
SESSION_SECURE=true
```

The web server document root should point to:

```text
public/
```

Runtime storage directories must be writable by the PHP worker.

Bhitti is designed for PHP-FPM-based deployments while preserving broad hosting compatibility.

The deployment environment itself is a developer or product-owner decision.

### Production Security Checklist

- Use HTTPS
- Keep `APP_DEBUG=false`
- Keep `.env` outside public access
- Do not expose Redis or Memcached publicly
- Escape dynamic HTML output
- Keep raw SQL developer-controlled
- Use database constraints for integrity
- Configure trusted proxies only when required
- Rebuild configuration and route caches after production changes

## Documentation

Full documentation:

[https://sayedsahin.github.io/bhitti-doc](https://sayedsahin.github.io/bhitti-doc)

Framework source:

[https://github.com/sayedsahin/bhitti-framework](https://github.com/sayedsahin/bhitti-framework)

Application source:

[https://github.com/sayedsahin/bhitti](https://github.com/sayedsahin/bhitti)

The full documentation contains dedicated guides for routing, middleware, controllers, views, Query Builder, database, migrations, seeders, sessions, cache, rate limiting, authentication, security, deployment, performance, and troubleshooting.

## Project Principle

> Provide the application infrastructure developers commonly need while keeping the framework fast, simple, explicit, and understandable.

## License

Bhitti is open-source software licensed under the MIT License.
