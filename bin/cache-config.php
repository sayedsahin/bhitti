<?php

declare(strict_types=1);

// Command: php run cache:config
// Recreates the config cache from .env + config/*.php.

require_once __DIR__ . '/../config/path.php';
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/app/Helpers/helper.php';

use App\Systems\Config\ConfigLoader;

require ROOT_PATH . '/bootstrap/dotenv.php';

$cacheFile = STORAGE_PATH . '/cache/config.php';
$items = ConfigLoader::load(ROOT_PATH . '/config');

ConfigLoader::writeCache($cacheFile, $items);

if (PHP_SAPI === 'cli') {
    echo "[✓] Config cache recreated successfully\n";
    echo "    Location: {$cacheFile}\n";
    echo '    Size: ' . number_format(filesize($cacheFile)) . " bytes\n";
}
