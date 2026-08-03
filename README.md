# Bhitti

**Bhitti** is a lightweight PHP framework built around three core principles: **Performance**, **Simplicity** and **Efficiency**.

## Requirements

- PHP 8.2 or newer
- Composer
- PDO
- PDO MySQL
- Mbstring

Optional extensions:

- APCu for APCu cache and rate limiting
- PhpRedis for Redis cache and rate limiting
- Memcached for Memcached cache and rate limiting
- GD for image resizing

## Installation

Clone or download the project, then install Composer dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

For Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate the optimized Composer autoloader when needed:

```bash
composer dump-autoload
```

## Environment Configuration

Configure the application in `.env`:

```dotenv
APP_NAME=Bhitti
DEBUG_MODE=true
BASE_URL=http://127.0.0.1:8000
APP_TIMEZONE=UTC

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=bhitti
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=native
SESSION_LIFETIME=7200
SESSION_SAMESITE=Lax
SESSION_SECURE=false

CACHE_DRIVER=file
CACHE_PREFIX=bhitti:cache:

RATE_LIMIT_STORE=file
RATE_LIMIT_PREFIX=bhitti:rate-limit:
```

After changing `.env` or a cached configuration file, rebuild the configuration cache:

```bash
php run cache:config
```

## Development Server

Run the PHP development server from the project root:

```bash
php -S 127.0.0.1:8000 -t public
```

Open:

```text
http://127.0.0.1:8000
```

The PHP development server is intended for local development only.

## Your First Page

A basic page requires three files:

```text
config/routes.php
app/Controllers/HomeController.php
app/Views/home.php
```

### Route

Add the route to `config/routes.php`:

```php
<?php

use App\Controllers\HomeController;

$route->get('/', [HomeController::class, 'index']);
```

### Controller

Create `app/Controllers/HomeController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

final class HomeController extends Controller
{
    public function index(): void
    {
        view('home', [
            'title' => 'Bhitti',
            'heading' => 'Welcome to Bhitti',
            'description' => 'A lightweight PHP framework for super-fast, simple and efficient web applications.',
        ]);
    }
}
```

### View

Create `app/Views/home.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
</head>
<body>
    <main>
        <h1><?= e($heading) ?></h1>

        <p><?= e($description) ?></p>
    </main>
</body>
</html>
```

The request flow becomes:

```text
GET /
→ HomeController::index()
→ view('home', $data)
→ app/Views/home.php
```

## Routing

Routes are defined in `config/routes.php`.

### Basic Routes

```php
$route->get('/users', [UserController::class, 'index']);
$route->post('/users', [UserController::class, 'store']);
```

### Route Parameters

```php
$route->get('/users/{id:\d+}', [UserController::class, 'show']);
```

Controller:

```php
public function show(string $id): Response
{
    $user = User::query()->find((int) $id);

    return response()->json([
        'user' => $user,
    ]);
}
```

### Route Middleware

```php
$route->get('/dashboard', [DashboardController::class, 'index', [
    Authenticated::class,
]]);
```

Parameterized middleware:

```php
$route->get('/admin', [AdminController::class, 'index', [
    Authenticated::class,
    [RoleMiddleware::class, ['admin']],
]]);
```

### API Routes

Routes beginning with `/api` use the API middleware stack:

```php
$route->get('/api/profile', [ProfileController::class, 'show', [
    BearerAuth::class,
]]);
```

## Controllers

Controllers are resolved through the service container.

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Bhitti\Http\Response;

final class UserController extends Controller
{
    public function index(): Response
    {
        $users = db()
            ->table('users')
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
    public function __construct(private ReportService $reports)
    {
    }
}
```

## Views

Views are plain PHP files stored in:

```text
app/Views/
```

Render a view:

```php
view('users.index', [
    'title' => 'Users',
    'users' => $users,
]);
```

This loads:

```text
app/Views/users/index.php
```

Always escape dynamic output:

```php
<h1><?= e($title) ?></h1>
```

Use `raw()` only for trusted HTML:

```php
<?= raw($trustedHtml) ?>
```

### View Loop

```php
<?php if ($users === []): ?>
    <p>No users found.</p>
<?php else: ?>
    <ul>
        <?php foreach ($users as $user): ?>
            <li>
                <?= e($user->name) ?>
                &lt;<?= e($user->email) ?>&gt;
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
```

### CSRF-Protected Form

```php
<form method="post" action="/users">
    <?= csrf_field() ?>

    <label for="name">Name</label>
    <input id="name" name="name" type="text">

    <button type="submit">Create User</button>
</form>
```

## Request

Use the request helper:

```php
$request = request();
```

### Form and Query Input

```php
$all = request()->all();
$email = request()->input('email');
$page = request()->query('page', 1);
$name = request()->post('name');
```

### JSON Input

```php
$data = request()->json();
$email = request()->json('email');
```

### Raw Body

```php
$body = request()->getRawBody();
```

### Files, Cookies and Headers

```php
$file = request()->file('avatar');
$theme = request()->cookie('theme', 'light');
$accept = request()->header('accept');
$token = request()->bearerToken();
```

### Request Metadata

```php
$method = request()->method();
$path = request()->path();
$url = request()->fullUrl();
$host = request()->host();
$ip = request()->ip();
$secure = request()->isSecure();
```

## Responses

### HTML Response

```php
return response()->html('<h1>Hello</h1>');
```

### JSON Response

```php
return response()->json([
    'status' => 'success',
    'data' => $data,
], 200);
```

### Headers and Status

```php
return response()
    ->status(201)
    ->header('X-App-Version', '1.0')
    ->json([
        'message' => 'Created',
    ], 201);
```

### Redirect

```php
return response()->redirect('/dashboard');
```

Redirect with flash data:

```php
return response()
    ->redirect('/login')
    ->with('success', 'Registration completed successfully.');
```

Back redirect:

```php
return response()
    ->redirect()
    ->with('error', 'Invalid input.')
    ->back();
```

## Middleware

Middleware returns `null` to continue or a `Response` to stop the request.

```php
<?php

declare(strict_types=1);

namespace App\Middlewares;

use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;

final class VerifiedEmail implements MiddlewareInterface
{
    public function handle(): ?Response
    {
        $user = \App\Supports\Auth::user();

        if (!$user || !$user->email_verified) {
            return response()->html(
                'Email verification required.',
                403
            );
        }

        return null;
    }
}
```

Global middleware stacks are configured in `config/middleware.php`:

```php
return [
    'web' => [
        WebHeaders::class,
        SessionStart::class,
        RateLimit::class,
        RememberMe::class,
        Csrf::class,
    ],

    'api' => [
        ApiHeaders::class,
        RateLimit::class,
    ],
];
```

## Validation

Create a validator:

```php
use Bhitti\Validation\Validator;

$validator = Validator::make(request()->all())
    ->required(['name', 'email'])
    ->string(['name', 'email'])
    ->email('email')
    ->max('name', 100);
```

Check errors:

```php
if ($validator->fails()) {
    return response()->json([
        'errors' => $validator->errors(),
    ], 422);
}
```

Get only validated fields:

```php
$data = $validator->validated();
```

Additional rules include:

```php
->nullable('phone')
->int('age')
->bool('active')
->min('password', 8)
->max('name', 100)
->in('status', ['active', 'inactive'])
->confirmed('password')
->sometimes('company_name', $callback)
->custom($callback)
->bail()
```

## Database

Bhittiuses PDO for database access.

```php
$users = db()
    ->table('users')
    ->select('id', 'name', 'email')
    ->where('status', 'active')
    ->order('created_at DESC')
    ->limit(20)
    ->get();
```

The database connection is reused during the request, while each `db()` call returns fresh Query Builder state.

## Models

Models are lightweight Query Builder subclasses:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Bhitti\Database\QueryBuilder;

final class User extends QueryBuilder
{
    protected string $table = 'users';
}
```

Use a fresh model query:

```php
$users = User::query()
    ->select('id', 'name', 'email')
    ->where('status', 'active')
    ->get();
```

Bhitti models do not provide a full Active Record lifecycle, dirty tracking or automatic relationships.

They provide a fixed table, reusable query methods and clean Query Builder access.

## Query Builder

### Conditions

```php
$user = db()
    ->table('users')
    ->where('email', $email)
    ->first();
```

```php
// With database connection
$users = db('sqlite')
    ->table('users')
    ->where('status', 'active')
    ->orWhere('role', 'admin')
    ->get();
```

```php
$users = db()
    ->table('users')
    ->whereNull('deleted_at')
    ->whereNotNull('email_verified_at')
    ->like('name', '%John%')
    ->get();
```

### Joins

```php
$users = db()
    ->table('users')
    ->leftJoin(
        'profiles',
        'profiles.user_id',
        '=',
        'users.id'
    )
    ->select('users.id', 'users.name', 'profiles.bio')
    ->get();
```

### Read Methods

```php
->get();
->first();
->find(5);
->exists();
->count();
->pluck('email');
->value('email');
```

### Insert

```php
$id = db()
    ->table('users')
    ->insert([
        'name' => 'Rahim',
        'email' => 'rahim@example.com',
    ], true);
```

### Update

```php
db()
    ->table('users')
    ->where('id', 5)
    ->update([
        'status' => 'active',
    ]);
```

### Delete

```php
db()
    ->table('users')
    ->where('id', 5)
    ->delete();
```

Builder-generated UPDATE and DELETE queries require a WHERE condition.

### Update or Insert

```php
db()
    ->table('settings')
    ->updateOrInsert(
        ['key' => 'theme'],
        ['value' => 'dark']
    );
```

### Raw SQL

Raw SELECT:

```php
$users = db()
    ->raw(
        'SELECT * FROM users WHERE status = ?',
        ['active']
    )
    ->get();
```

Raw UPDATE:

```php
db()
    ->raw(
        'UPDATE users SET status = ? WHERE id = ?',
        ['active', 5]
    )
    ->execute();
```

Raw INSERT with inserted ID:

```php
$id = db()
    ->raw(
        'INSERT INTO users (name, email) VALUES (?, ?)',
        ['Rahim', 'rahim@example.com']
    )
    ->execute(true);
```

Values are bound through PDO.

Table names, column names, operators, order expressions, joins and raw SQL must remain developer-controlled.

## Authentication

### Login

```php
use App\Supports\Auth;

Auth::login((int) $user->id);
```

### Authentication State

```php
Auth::check();
Auth::id();
Auth::user();
```

### Logout

```php
Auth::logout();
```

### Route Protection

```php
$route->get('/dashboard', [DashboardController::class, 'index', [
    Authenticated::class,
]]);
```

### Bearer Authentication

```php
$route->get('/api/profile', [ProfileController::class, 'show', [
    BearerAuth::class,
]]);
```

Client request:

```http
Authorization: Bearer RAW_TOKEN
```

Raw authentication tokens are sent to the client while their SHA-256 hashes are stored in the database.

## Roles

Check a role:

```php
if (Role::has('admin')) {
    // ...
}
```

Check any role:

```php
Role::any(['admin', 'editor']);
```

Check all roles:

```php
Role::all(['admin', 'verified']);
```

Assign or remove roles:

```php
Role::assign($userId, 'user');
Role::remove($userId, 'user');
```

Role results are cached for the current request.

## Sessions

```php
use Bhitti\Session\Session;

Session::set('key', $value);

$value = Session::get('key', $default);

Session::forget('key');
Session::regenerate();
Session::flush();
Session::destroy();
Session::close();
```

Available session drivers:

```text
native
null
```

Web requests initialize sessions according to configuration. API requests remain session-free by default.

## Cache

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
    function () use ($id) {
        return User::query()->find($id);
    }
);
```

Available cache drivers:

| Driver | Recommended Use |
|---|---|
| Array | Tests and request-local temporary cache |
| File | Portable single-server applications |
| APCu | Supported single-server web environments |
| Redis | Shared cache across servers, containers and CLI |
| Memcached | Shared distributed cache |

Only the configured cache driver is initialized.

## Rate Limiting

Rate limiting is available for:

- guest web requests
- authenticated web requests
- API requests
- sensitive routes such as login and registration

Available drivers:

```text
file
apcu
redis
memcached
```

Direct usage:

```php
$result = RateLimiter::hit(
    'login:ip:' . request()->ip(),
    5,
    60
);

if (!$result->allowed()) {
    return response()->json([
        'error' => 'Too many attempts.',
        'retry_after' => $result->retryAfter(),
    ], 429);
}
```

Rate-limit responses may include:

```text
X-RateLimit-Limit
X-RateLimit-Remaining
X-RateLimit-Reset
Retry-After
```

## Service Container

Configure bindings in `config/container.php`:

```php
return [
    'singletons' => [
        \Bhitti\Database\Database::class,
    ],

    'bindings' => [],
];
```

Interface binding:

```php
'bindings' => [
    LoggerInterface::class => FileLogger::class,
],
```

Use singletons only for services that should reuse one instance during a request.

Stateful objects such as Query Builder and Response should not be registered as singletons.

## Exception Handling

Bhitti registers a central handler for:

- uncaught exceptions
- PHP errors
- fatal shutdown errors
- web error responses
- API JSON error responses
- CLI error output

With debug mode disabled:

```json
{
    "error": "Internal Server Error"
}
```

With debug mode enabled, development responses include exception details and stack traces.

Never enable debug output on a public production server.

## Configuration

Read configuration:

```php
$name = config('app.name');
$debug = config('app.debug', false);
```

Set request-local configuration:

```php
config([
    'app.debug' => true,
]);
```

Read environment values inside config files:

```php
'host' => env('DB_HOST', '127.0.0.1'),
```

Configuration is cached in:

```text
storage/cache/config.cache
```

## Command-Line Tools

Rebuild configuration cache:

```bash
php run cache:config
```

Rebuild route cache:

```bash
php run cache:route
```

Clear application cache:

```bash
php run cache:clear
```

Generate optimized Composer autoload files:

```bash
composer dump-autoload --optimize
```

## Directory Structure

```text
pkathamo/
├── app/
│   ├── Controllers/
│   ├── Helpers/
│   ├── Middlewares/
│   ├── Models/
│   ├── Supports/
│   ├── Systems/
│   ├── Validation/
│   └── Views/
├── bin/
├── bootstrap/
├── config/
├── database/
├── docs/
├── public/
├── storage/
├── tests/
├── .env.example
├── composer.json
└── README.md
```

### Important Directories

| Directory | Purpose |
|---|---|
| `app/Controllers` | Web and API controllers |
| `app/Helpers` | Globally available helper functions |
| `app/Middlewares` | Application middleware |
| `app/Models` | Lightweight Query Builder models |
| `app/Supports` | Authentication, roles and rate limiting |
| `app/Systems` | Framework core systems |
| `app/Validation` | Validator and validation exceptions |
| `app/Views` | Plain PHP view templates |
| `bootstrap` | Application bootstrap scripts |
| `config` | Framework and application configuration |
| `database` | Database schema and backup files |
| `docs` | Complete framework documentation |
| `public` | Public web-server document root |
| `storage` | Generated cache and runtime files |

## Security

Bhitti includes:

- PDO-bound query values
- CSRF protection
- secure native session settings
- HttpOnly cookies
- SameSite cookies
- hashed authentication tokens
- trusted proxy validation
- web and API security headers
- request rate limiting
- safe same-domain back redirects
- generic production error responses
- UPDATE and DELETE WHERE protection

Application developers must still ensure that:

- dynamic output is escaped with `e()`
- raw SQL remains developer-controlled
- SQL identifiers are selected through allowlists
- production uses HTTPS
- debug mode is disabled in production
- Redis and Memcached are not publicly exposed
- database constraints enforce uniqueness and relationships
- secrets are never committed to source control

## Production Deployment

Recommended production steps:

```bash
composer install --no-dev --optimize-autoloader
php run cache:config
php run cache:route
```

Production environment:

```dotenv
DEBUG_MODE=false
SESSION_SECURE=true
```

The web-server document root must point to:

```text
public/
```

Required storage directories must be writable by the PHP worker.

Generated configuration and route caches should be created on the target server, not included in the public release archive.

Do not distribute:

```text
.env
storage/cache/config.cache
storage/cache/route.cache
runtime cache files
```

## Documentation

Complete documentation is available in:

[Read the Bhitti Documentation](docs/README.md)

The documentation includes dedicated guides for:

- installation
- request lifecycle
- configuration
- routing
- controllers
- requests and responses
- views and helpers
- middleware
- validation
- Query Builder and models
- sessions and authentication
- cache and rate limiting
- exception handling
- security
- deployment
- performance
- troubleshooting

## Intended Use

Bhitti is suitable for:

- web applications
- REST APIs
- business applications
- authentication-based portals
- CRUD systems
- admin panels
- internal tools
- small and medium SaaS applications

It is especially useful when a simple router is not enough, but a large full-stack framework introduces more complexity than the application requires.

## Project Principle

> Provide the features most applications need while keeping the framework fast, simple and understandable.
````
