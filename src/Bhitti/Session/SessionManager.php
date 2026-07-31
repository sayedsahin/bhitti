<?php

declare(strict_types=1);

namespace Bhitti\Session;

use Bhitti\Session\Drivers\NativeSession;
use Bhitti\Session\Drivers\NullSession;
use RuntimeException;

final class SessionManager
{
    public static function configure(array $config): void
    {
        $driverName = $config['driver'] ?? 'native';

        $driver = match ($driverName) {
            'native' => new NativeSession($config),
            'null' => new NullSession($config),

            default => throw new RuntimeException(
                'Unsupported session driver: ' . $driverName
            ),
        };

        Session::setDriver($driver);
    }
}