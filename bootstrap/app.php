<?php

declare(strict_types=1);

use Bhitti\Core\Application;

$basePath = dirname(__DIR__);

defined('ROOT_PATH') || define('ROOT_PATH', $basePath);
defined('APP_PATH') || define('APP_PATH', ROOT_PATH . '/app');
defined('VIEW_PATH') || define('VIEW_PATH', APP_PATH . '/Views');
defined('STORAGE_PATH') || define('STORAGE_PATH', ROOT_PATH . '/storage');

require ROOT_PATH . '/vendor/autoload.php';

return new Application(ROOT_PATH);