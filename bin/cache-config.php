<?php

declare(strict_types=1);

// Command: php run cache:config
// Recreates the config cache from .env + config/*.php.
use Bhitti\Config\ConfigLoader;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/bootstrap/app.php';


$envFile = ROOT_PATH . '/.env';
$cacheFile = STORAGE_PATH . '/cache/config.cache';

if (is_file($envFile)) {
    $dotenv = new Dotenv();
    $dotenv->usePutenv();
    $dotenv->load($envFile);
}

$items = ConfigLoader::load(ROOT_PATH . '/config');

ConfigLoader::writeCache($cacheFile, $items);

if (PHP_SAPI === 'cli') {
    echo "[✓] Config cache recreated successfully\n";
    echo "    Location: {$cacheFile}\n";
    echo '    Size: ' . number_format(filesize($cacheFile)) . " bytes\n";
}
