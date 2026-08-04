<?php

declare(strict_types=1);

use Bhitti\Routing\RouteCollector;
use FastRoute\RouteCollector as FastRouteCollector;

if (PHP_SAPI !== 'cli') {
    exit('This command can only be run from the CLI.');
}

require dirname(__DIR__) . '/bootstrap/app.php';

$cacheFile = STORAGE_PATH . '/cache/route.cache.php';

if (is_file($cacheFile) && !unlink($cacheFile)) {
    throw new RuntimeException(
        "Unable to remove existing route cache: {$cacheFile}"
    );
}

\FastRoute\cachedDispatcher(
    static function (FastRouteCollector $route): void {
        require ROOT_PATH . '/config/routes.php';
    },
    [
        'routeCollector' => RouteCollector::class,
        'cacheFile' => $cacheFile,
        'cacheDisabled' => false,
    ]
);

clearstatcache(true, $cacheFile);

if (file_exists($cacheFile)) {
    $fileSize = filesize($cacheFile);
    echo "[✓] Route cache generated successfully!\n";
    echo "    Location: $cacheFile\n";
    echo "    Size: " . number_format($fileSize, 0) . " bytes\n";

    if (php_sapi_name() === 'cli') {
        echo "\n✓ Run this before production deployment\n";
    }
} else {
    echo "[✗] Failed to generate route cache\n";
    exit(1);
}