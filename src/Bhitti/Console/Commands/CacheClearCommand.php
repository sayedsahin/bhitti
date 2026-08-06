<?php

declare(strict_types=1);

namespace Bhitti\Console\Commands;

use Bhitti\Cache\Cache;
use Bhitti\Console\Input;
use Bhitti\Console\Output;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class CacheClearCommand extends MigrationCommand
{
    public function handle(Input $input, Output $output): int
    {
        $cachePath = ROOT_PATH . '/storage/cache';

        $failures = [];
        $warnings = [];

        $deletedFiles = 0;
        $deletedDirectories = 0;

        $storeCleared = false;
        $driver = 'unknown';
        try {
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
            $output->success("Application cache store cleared: {$driver}");
        }

        $output->line("  Deleted cache files: {$deletedFiles}");
        $output->line("  Deleted cache directories: {$deletedDirectories}");

        foreach ($warnings as $warning) {
            $output->warning($warning);
        }

        if ($failures !== []) {
            $output->warning('Cache clear completed with errors.' . implode("\n- ", $failures));

            return 0;
        }

        return 0;
    }
}