<?php

declare(strict_types=1);

namespace Bhitti\Session\Drivers;

use Bhitti\Session\SessionInterface;
use Bhitti\Http\TrustedProxy;

final class NativeSession implements SessionInterface
{
    private bool $started = false;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        session_name($this->config['name'] ?? 'BHITTISESSID');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
            'use_strict_mode' => 1,
            'use_only_cookies' => 1,
            'cookie_lifetime' => 0,
            'cookie_path' => $this->config['path'],
            'cookie_domain' => $this->config['domain'] ,
            'cookie_httponly' => $this->config['httponly'],
            'cookie_secure' => $this->config['secure'] && TrustedProxy::isSecureRequest($_SERVER),
            'cookie_samesite' => $this->config['samesite'] ?? 'Lax',
            'gc_maxlifetime' => $this->config['lifetime'] ?? 7200,
        ]);
        }

        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function flush(): void
    {
        $this->start();
        $_SESSION = [];
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $this->start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();

        $this->started = false;
    }

    public function close(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->started = false;

            return;
        }

        session_write_close();
        $this->started = false;
    }
}
