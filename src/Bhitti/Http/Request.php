<?php

declare(strict_types=1);

namespace Bhitti\Http;

final class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;
    private ?string $rawBody = null;
    private ?string $resolvedPath = null;
    private bool $jsonParsed = false;
    private array $jsonBody = [];

    private function __construct(
        array $get,
        array $post,
        array $server,
        array $files,
        array $cookies
    ) {
        $this->get     = $get;
        $this->post    = $post;
        $this->server  = $server;
        $this->files   = $files;
        $this->cookies = $cookies;
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE);
    }

    /* -----------------------
       Basic Accessors
    ----------------------- */

    public function all(): array
    {
        // POST takes precedence over GET
        return $this->post + $this->get;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        return $this->get[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /* -----------------------
       Raw & JSON Body
    ----------------------- */

    public function getRawBody(): string
    {
        if ($this->rawBody === null) {
            $this->rawBody = file_get_contents('php://input') ?: '';
        }
        return $this->rawBody;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if (!$this->jsonParsed) {
            $decoded = json_decode(
                $this->getRawBody(),
                true
            );

            $this->jsonBody = is_array($decoded)
                ? $decoded
                : [];

            $this->jsonParsed = true;
        }

        if ($key === null) {
            return $this->jsonBody;
        }

        return $this->jsonBody[$key]
            ?? $default;
    }

    /* -----------------------
       Meta
    ----------------------- */

    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    public function path(): string
    {
        if ($this->resolvedPath !== null) {
            return $this->resolvedPath;
        }

        $uri = $this->server['REQUEST_URI'] ?? '/';

        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $path = rawurldecode($path);

        return $this->resolvedPath = '/' . trim($path, '/');
    }

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';

        return $scheme . '://' . $this->host() . ($this->server['REQUEST_URI'] ?? '/');
    }

    public function host(): string
    {
        if (TrustedProxy::isSecureRequest($this->server)) {
            $forwardedHost =
                $this->server['HTTP_X_FORWARDED_HOST'] ?? '';

            if ($forwardedHost !== '') {
                return trim(explode(',', $forwardedHost)[0]);
            }
        }

        return $this->server['HTTP_HOST'] ?? 'localhost';
    }

    public function ip(): ?string
    {
        // return $this->server['REMOTE_ADDR'] ?? null;
        return TrustedProxy::clientIp($this->server);
    }

    public function isSecure(): bool
    {
        // return isset($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
        return TrustedProxy::isSecureRequest($this->server);
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtoupper(str_replace('-', '_', trim($key)));

        if ($key === '') {
            return $default;
        }

        /*
        * Apache/FastCGI in some configurations
        * Authorization can be in either of these two keys.
        */
        if ($key === 'AUTHORIZATION') {
            return $this->server['HTTP_AUTHORIZATION']
                ?? $this->server['REDIRECT_HTTP_AUTHORIZATION']
                ?? $default;
        }

        /*
        * CONTENT_TYPE and CONTENT_LENGTH are usually
        * Without the HTTP_ prefix.
        */
        if ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
            return $this->server[$key]
                ?? $this->server[
                    'HTTP_' . $key
                ]
                ?? $default;
        }

        return $this->server['HTTP_' . $key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization');

        if (!is_string($header) || strncasecmp($header, 'Bearer ', 7) !== 0) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token !== '' ? $token : null;
    }

    public function isApi(): bool
    {
        $path = $this->path();

        return $path === '/api'
            || str_starts_with($path, '/api/');
    }
}
