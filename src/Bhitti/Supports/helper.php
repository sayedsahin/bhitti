<?php

use Bhitti\Cache\Cache;
use Bhitti\Database\Database;
use Bhitti\Session\Session;
use Bhitti\Config\Config;
use Bhitti\Config\Env;
use App\Supports\Flash;
use Bhitti\Database\DB;
use Bhitti\Http\Request;
use Bhitti\Http\Response;

if (!function_exists('cache')) {
    function cache(): Cache
    {
        static $cache;

        return $cache ??= new Cache();
    }
}

if (!function_exists('db')) {
    function db(?string $connection = null): DB
    {
        global $container;

        return new DB(
            $container->make(Database::class),
            $connection
        );
    }
}

/**
 * @return Session
 */

if (!function_exists('session')) {
    function session()
    {
        static $proxy = null;

        if ($proxy === null) {
            $proxy = new class {
                public function __call($method, $args)
                {
                    return Session::$method(...$args);
                }
            };
        }

        return $proxy;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        static $env = null;
        if ($env === null) {
            $env = new Env;
        }

        return $env->get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string|array|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Config::all();
        }

        if (is_array($key)) {
            Config::setMany($key);
            return null;
        }

        return Config::get($key, $default);
    }
}

if (!function_exists('value')) {
    function value(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }
}


if (!function_exists('request')) {
	function request(): Request
	{
		global $container;

        return $container->make(Request::class);
	}
}

if (!function_exists('response')) {
	function response(string $content = '', int $status = 200): Response
	{
		return new Response($content, $status);
	}
}


if (!function_exists('view')) {
    function view(string $view, array $data = []): void
    {
        $path = view_path($view);

        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);
        require $path;
    }
}

if (!function_exists('view_path')) {
    function view_path(string $view): string
    {
        return APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// another framework like {!! !!}
if (!function_exists('raw')) {
    function raw(mixed $value): string
    {
        return (string) $value;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        $token = Session::get('_csrf');

        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }

        return $token;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}


if (!function_exists('flash')) {

    function flash(): string
    {
        return Flash::render();
    }
}

if (!function_exists('is_ajax')) {
    function is_ajax(): bool
    {
        $header = request()->header('x-requested-with');
        return !empty($header) && strtolower($header) === 'xmlhttprequest';
    }
}

if (!function_exists('is_api_request')) {
    function is_api_request(): bool
    {
        return request()->isApi();
    }
}

if (!function_exists('pr')) {
    function pr(mixed $array)
    {
        echo "<pre>";
        print_r($array);
        echo "</pre>";
    }
}

if (!function_exists('dd')) {
    function dd(mixed $array)
    {
        echo "<pre>";
        var_dump($array);
        echo "</pre>";
        exit;
    }
}