<?php

declare(strict_types=1);

// Command: php bin/cache-clear.php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This command can only be run from the CLI.');
}

require_once __DIR__ . '/../config/path.php';
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/app/Helpers/helper.php';

use App\Systems\Cache\Cache;

$cachePath = STORAGE_PATH . '/cache';
$failures = [];
$warnings = [];
$deletedFiles = 0;
$deletedDirectories = 0;
$storeCleared = false;
$driver = 'unknown';

/*
|--------------------------------------------------------------------------
| Load Effective Configuration
|--------------------------------------------------------------------------
| Load the same effective configuration used by the application before
| deleting config.php. This ensures the currently active cache store is
| cleared first.
*/
try {
    require ROOT_PATH . '/bootstrap/config.php';

    $driver = strtolower(trim((string) config('cache.driver', 'file')));

    require ROOT_PATH . '/bootstrap/cache.php';

    Cache::flush();
    $storeCleared = true;

    if ($driver === 'apcu') {
        $warnings[] = 'APCu was cleared only for the current CLI SAPI. PHP-FPM or Apache APCu storage may require a web-process restart or a web-context clear command.';
    }
} catch (Throwable $exception) {
    $failures[] = "Unable to clear the active [{$driver}] cache store: {$exception->getMessage()}";
}

/*
|--------------------------------------------------------------------------
| Clear Generated Files
|--------------------------------------------------------------------------
| Preserve repository placeholder/documentation files while removing
| config cache, route cache, file-cache entries, and nested cache folders.
*/
if (is_dir($cachePath)) {
    $protectedFiles = [
        '.gitkeep',
        'README.md',
    ];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cachePath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();
        if (in_array($item->getFilename(), $protectedFiles, true)) {
            continue;
        }

        if ($item->isLink() || $item->isFile()) {
            if (@unlink($path)) {
                $deletedFiles++;
            } else {
                $failures[] = "Unable to delete file: {$path}";
            }

            continue;
        }

        if ($item->isDir()) {
            if (@rmdir($path)) {
                $deletedDirectories++;
            } elseif (is_dir($path)) {
                $failures[] = "Unable to delete directory: {$path}";
            }
        }
    }
}

if ($storeCleared) {
    fwrite(STDOUT, "Application cache store cleared: {$driver}\n");
}

fwrite(
    STDOUT,
    "Deleted cache files: {$deletedFiles}\n"
    . "Deleted cache directories: {$deletedDirectories}\n"
);

foreach ($warnings as $warning) {
    fwrite(STDOUT, "Warning: {$warning}\n");
}

if ($failures !== []) {
    fwrite(
        STDERR,
        "Cache clear completed with errors:\n- "
        . implode("\n- ", $failures)
        . "\n"
    );

    exit(1);
}

fwrite(STDOUT, "Cache cleared successfully.\n");

exit(0);
