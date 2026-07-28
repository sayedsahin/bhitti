<?php

namespace Bhitti\Session\Drivers;

use Bhitti\Session\SessionInterface;

final class NullSession implements SessionInterface
{
    public function start(): void {}
    public function get(string $key, mixed $default = null): mixed { return $default; }
    public function set(string $key, mixed $value): void {}
    public function forget(string $key): void {}
    public function flush(): void {}
    public function regenerate(): void {}
    public function destroy(): void {}
    public function close(): void {}
}

