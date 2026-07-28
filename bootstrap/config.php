<?php

declare(strict_types=1);

use Bhitti\Config\Config;
use Bhitti\Config\ConfigLoader;


$cacheFile = STORAGE_PATH . '/cache/config.php';

if (is_file($cacheFile)) {
    Config::load(ConfigLoader::loadFromCache($cacheFile));
} else {
    require ROOT_PATH . '/bootstrap/dotenv.php';

    Config::load(
        ConfigLoader::load(ROOT_PATH . '/config')
    );
}

date_default_timezone_set((string) config('app.timezone', 'UTC'));
define('BASE_URL', (string) config('app.url', 'http://localhost'));

