<?php

declare(strict_types=1);

namespace Bhitti\Config;

use LogicException;
use RuntimeException;

final class ConfigLoader
{
    private const IGNORED_FILES = [
        'routes.php',
    ];

    public static function load(string $configPath): array
    {
        if (!is_dir($configPath)) {
            throw new RuntimeException("Config directory not found: {$configPath}");
        }

        $items = [];
        $files = glob(rtrim($configPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];

        sort($files);

        foreach ($files as $file) {
            $basename = basename($file);

            if (in_array($basename, self::IGNORED_FILES, true)) {
                continue;
            }

            $key = pathinfo($file, PATHINFO_FILENAME);
            $config = require $file;

            if (!is_array($config)) {
                throw new RuntimeException("Config file must return an array: {$file}");
            }

            $items[$key] = $config;
        }

        return $items;
    }

    public static function loadFromCache(string $cacheFile): array
    {
        if (!is_file($cacheFile)) {
            throw new RuntimeException("Config cache file not found: {$cacheFile}");
        }

        $items = require $cacheFile;

        if (!is_array($items)) {
            throw new RuntimeException("Config cache file must return an array: {$cacheFile}");
        }

        return $items;
    }

    public static function writeCache(string $cacheFile, array $items): void
    {
        self::ensureCacheable($items);

        $directory = dirname($cacheFile);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create config cache directory: {$directory}");
        }

        $content = '<?php' . PHP_EOL . PHP_EOL
            . 'declare(strict_types=1);' . PHP_EOL . PHP_EOL
            . 'return ' . var_export($items, true) . ';' . PHP_EOL;

        $tmpFile = $cacheFile . '.tmp';

        if (file_put_contents($tmpFile, $content, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write config cache file: {$tmpFile}");
        }

        if (!rename($tmpFile, $cacheFile)) {
            @unlink($tmpFile);
            throw new RuntimeException("Unable to move config cache file into place: {$cacheFile}");
        }
    }

    private static function ensureCacheable(mixed $value, string $path = 'config'): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                self::ensureCacheable($item, $path . '.' . (string) $key);
            }

            return;
        }

        if (is_scalar($value) || $value === null) {
            return;
        }

        throw new LogicException("Config value is not cacheable at {$path}. Use only arrays, strings, numbers, booleans, and null.");
    }
}
