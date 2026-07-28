<?php

use Bhitti\Session\Drivers\NativeSession;
use Bhitti\Session\Drivers\NullSession;
use Bhitti\Session\Session;

$config = (array) config('session');

switch ($config['driver']) {
    case 'native':
        $driver = new NativeSession($config);
        break;

    case 'null':
        $driver = new NullSession($config); // change to FileSession
        break;

    default:
        throw new RuntimeException('Invalid session driver');
}

Session::setDriver($driver);
