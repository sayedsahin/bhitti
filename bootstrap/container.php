<?php

use Bhitti\Core\Container;

// $config = require ROOT_PATH . '/config/container.php';

$container = new Container();

$config = config('container');

/*
|--------------------------------------------------------------------------
| Register Singletons
|--------------------------------------------------------------------------
*/
foreach ($config['singletons'] as $class) {
    $container->singleton($class);
}

/*
|--------------------------------------------------------------------------
| Register Bindings
|--------------------------------------------------------------------------
*/
foreach ($config['bindings'] as $abstract => $concrete) {
    $container->bind($abstract, $concrete);
}

return $container;
