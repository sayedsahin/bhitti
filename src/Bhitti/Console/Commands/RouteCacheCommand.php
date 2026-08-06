<?php

declare(strict_types=1);

namespace Bhitti\Console\Commands;

use Bhitti\Console\Input;
use Bhitti\Console\Output;
use Bhitti\Routing\RouteCollector;
use RuntimeException;
use FastRoute\RouteCollector as FastRouteCollector;

final class RouteCacheCommand extends MigrationCommand
{
    public function handle(Input $input, Output $output): int
    {
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
            $output->success("Route cache generated successfully");
            $output->line("  Location: {$cacheFile}");
            $output->line("  Size: " . number_format(filesize($cacheFile), 0) . " bytes");
        } else {
            $output->warning("Failed to generate route cache");
        }

        return 0;
    }
}