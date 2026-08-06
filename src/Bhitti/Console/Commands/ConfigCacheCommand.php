<?php

declare(strict_types=1);

namespace Bhitti\Console\Commands;

use Bhitti\Config\ConfigLoader;
use Bhitti\Console\Input;
use Bhitti\Console\Output;
use Symfony\Component\Dotenv\Dotenv;

final class ConfigCacheCommand extends MigrationCommand
{
    public function handle(Input $input, Output $output): int
    {
        $envFile = ROOT_PATH . '/.env';
        $cacheFile = STORAGE_PATH . '/cache/config.cache.php';

        if (is_file($envFile)) {
            $dotenv = new Dotenv();
            $dotenv->usePutenv();
            $dotenv->load($envFile);
        }

        $items = ConfigLoader::load(ROOT_PATH . '/config');

        ConfigLoader::writeCache($cacheFile, $items);

        $output->success("Config cache recreated successfully");
        $output->line("  Location: {$cacheFile}");
        $output->line("  Size: " . number_format(filesize($cacheFile), 0) . " bytes");

        return 0;
    }
}