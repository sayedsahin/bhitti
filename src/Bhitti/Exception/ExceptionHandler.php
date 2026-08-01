<?php

declare(strict_types=1);

namespace Bhitti\Exception;

use ErrorException;
use Throwable;

final class ExceptionHandler
{
    private const FATAL_ERRORS = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR,
    ];

    private const LOG_ONLY_ERRORS = [
        E_DEPRECATED,
        E_USER_DEPRECATED,
    ];

    private static bool $debug = false;
    private static bool $isApi = false;
    private static bool $handling = false;
    private static bool $registered = false;

    public static function register(bool $debug, bool $isApi): void
    {
        self::$debug = $debug;
        self::$isApi = $isApi;

        if (self::$registered) {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    public static function handleException(Throwable $exception): never
    {
        if (self::$handling) {
            self::fallback();
        }

        self::$handling = true;

        self::clearOutput();
        error_log((string) $exception);
        self::render($exception);

        exit(PHP_SAPI === 'cli' ? 1 : 0);
    }

    public static function handleError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        if (in_array($severity, self::LOG_ONLY_ERRORS, true)) {
            error_log(sprintf(
                'PHP %s: %s in %s on line %d',
                $severity === E_USER_DEPRECATED
                    ? 'User Deprecated'
                    : 'Deprecated',
                $message,
                $file,
                $line
            ));

            return true;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], self::FATAL_ERRORS, true)) {
            return;
        }

        if (self::$handling) {
            self::fallback();
        }

        self::handleException(
            new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            )
        );
    }

    private static function render(Throwable $exception): void
    {
        if (PHP_SAPI === 'cli') {
            self::renderCli($exception);
            return;
        }

        if (self::$isApi) {
            self::renderJson($exception);
            return;
        }

        self::renderHtml($exception);
    }

    private static function renderJson(Throwable $exception): void
    {
        $data = self::$debug
            ? [
                'error' => 'Internal Server Error',
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ]
            : [
                'error' => 'Internal Server Error',
            ];

        $json = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_INVALID_UTF8_SUBSTITUTE
        );

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
        }

        echo $json !== false
            ? $json
            : '{"error":"Internal Server Error"}';
    }

    private static function renderHtml(Throwable $exception): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        if (!self::$debug) {
            echo '<h1>Internal Server Error</h1>';
            return;
        }

        $class = self::escape($exception::class);
        $message = self::escape($exception->getMessage());
        $file = self::escape($exception->getFile());
        $trace = self::escape($exception->getTraceAsString());

        echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex,nofollow">
    <title>Application Error</title>
</head>
<body>
    <h1>{$class}</h1>
    <p>{$message}</p>
    <p>{$file}:{$exception->getLine()}</p>
    <pre>{$trace}</pre>
</body>
</html>
HTML;
    }

    private static function renderCli(Throwable $exception): void
    {
        $message = self::$debug
            ? (string) $exception
            : 'Internal Server Error';

        fwrite(STDERR, $message . PHP_EOL);
    }

    private static function fallback(): never
    {
        self::clearOutput();

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Internal Server Error' . PHP_EOL);
            exit(1);
        }

        if (!headers_sent()) {
            http_response_code(500);
            header(
                self::$isApi
                    ? 'Content-Type: application/json; charset=UTF-8'
                    : 'Content-Type: text/plain; charset=UTF-8'
            );
        }

        echo self::$isApi
            ? '{"error":"Internal Server Error"}'
            : 'Internal Server Error';

        exit;
    }

    private static function clearOutput(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}