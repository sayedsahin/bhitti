<?php

declare(strict_types=1);

use Bhitti\Cache\Cache;

// Command: php bin/cache-clear.php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command can only be run from the CLI.\n");
    exit(1);
}

$cachePath = dirname(__DIR__) . '/storage/cache';

$failures = [];
$warnings = [];

$deletedFiles = 0;
$deletedDirectories = 0;

$storeCleared = false;
$driver = 'unknown';

/*
|--------------------------------------------------------------------------
| Bootstrap Application
|--------------------------------------------------------------------------
| The application must be booted before deleting config.php so the active
| cache driver and its effective configuration are available.
*/
try {
    $app = require dirname(__DIR__) . '/bootstrap/app.php';

    $app->boot();

    $driver = strtolower(
        trim((string) config('cache.driver', 'file'))
    );

    /*
    |--------------------------------------------------------------------------
    | Clear Active Cache Store
    |--------------------------------------------------------------------------
    | Cache::flush() must be prefix-scoped for Redis and Memcached. It must
    | never globally flush a shared Redis database or Memcached server.
    */
    Cache::flush();

    $storeCleared = true;

    if ($driver === 'apcu') {
        $warnings[] = 'APCu was cleared only for the CLI process. PHP-FPM or Apache APCu storage may require a web-context clear operation or process restart.';
    }
} catch (Throwable $exception) {
    $failures[] = sprintf(
        'Unable to clear the active [%s] cache store: %s',
        $driver,
        $exception->getMessage()
    );
}

/*
|--------------------------------------------------------------------------
| Clear Generated Cache Files
|--------------------------------------------------------------------------
| This removes:
|
| - Config cache
| - Route cache
| - File-cache entries
| - Generated nested cache directories
|
| Repository placeholder and documentation files are preserved.
*/
if (is_dir($cachePath)) {
    $protectedFiles = [
        '.gitkeep',
        'README.md',
    ];

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $cachePath,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $filename = $item->getFilename();

            if (in_array($filename, $protectedFiles, true)) {
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

            if (!$item->isDir()) {
                continue;
            }

            /*
             * A directory may still contain a protected .gitkeep or README.
             * In that case it should remain and must not be reported as an
             * error.
             */
            $contents = @scandir($path);

            if ($contents === false) {
                $failures[] = "Unable to read directory: {$path}";
                continue;
            }

            $remainingItems = array_diff($contents, ['.', '..']);

            if ($remainingItems !== []) {
                continue;
            }

            if (@rmdir($path)) {
                $deletedDirectories++;
            } elseif (is_dir($path)) {
                $failures[] = "Unable to delete directory: {$path}";
            }
        }
    } catch (Throwable $exception) {
        $failures[] = 'Unable to scan the cache directory: '
            . $exception->getMessage();
    }
}

clearstatcache();

/*
|--------------------------------------------------------------------------
| Output Result
|--------------------------------------------------------------------------
*/
if ($storeCleared) {
    fwrite(
        STDOUT,
        "Application cache store cleared: {$driver}\n"
    );
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