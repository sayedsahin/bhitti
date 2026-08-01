<?php

declare(strict_types=1);

use Bhitti\Core\Application;

$basePath = dirname(__DIR__);

define('ROOT_PATH', $basePath);
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');
define('STORAGE_PATH', ROOT_PATH . '/storage');

require ROOT_PATH . '/vendor/autoload.php';

return new Application(ROOT_PATH);