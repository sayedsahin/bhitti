<?php

namespace Bhitti\Session;

interface SessionInterface
{
    public function start(): void;
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function forget(string $key): void;
    public function flush(): void;
    public function regenerate(): void;
    public function destroy(): void;
    public function close(): void;
}
