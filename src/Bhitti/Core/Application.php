<?php

declare(strict_types=1);

namespace Bhitti\Core;

use Bhitti\Cache\CacheManager;
use Bhitti\Config\Config;
use Bhitti\Config\ConfigLoader;
use Bhitti\Exception\ExceptionHandler;
use Bhitti\Http\Middleware\MiddlewareKernel;
use Bhitti\Http\Request;
use Bhitti\Routing\RouteDispatcher;
use Bhitti\Routing\Router;
use RuntimeException;
use Symfony\Component\Dotenv\Dotenv;

final class Application
{
    private Container $container;


    private bool $booted = false;

    public function __construct(private readonly string $basePath)
    {
        $this->container = new Container();

        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);

        /*
         * Existing helpers use:
         *
         * global $container;
         */
        $GLOBALS['container'] = $this->container;
    }

    public function run(Request $request): void
    {
        ExceptionHandler::register(false, $request->isApi());

        $this->container->instance(Request::class, $request);

        $this->boot($request->isApi());


        $this->container
            ->make(Kernel::class)
            ->handle($request);
    }

    public function boot(bool $isApi = false): void
    {
        if ($this->booted) {
            return;
        }

        $this->loadConfiguration();

        ExceptionHandler::register(
            config('app.debug'),
            $isApi
        );

        $this->registerCoreServices();
        $this->registerContainerBindings();
        CacheManager::configure((array) config('cache', []));

        $this->bootApplicationServices();

        $this->booted = true;

    }

    private function bootApplicationServices(): void
    {
        $file = ROOT_PATH . '/bootstrap/services.php';

        if (!is_file($file)) {
            return;
        }

        $bootstrap = require $file;

        if (!is_callable($bootstrap)) {
            throw new RuntimeException(
                'bootstrap/services.php must return a callable.'
            );
        }

        $bootstrap($this);
    }

    private function loadConfiguration(): void
    {
        $cacheFile = STORAGE_PATH. '/cache/config.cache';

        if (is_file($cacheFile)) {
            Config::load(
                ConfigLoader::loadFromCache($cacheFile)
            );
        } else {
            $envFile = ROOT_PATH . '/.env';

            if (is_file($envFile)) {
                $dotenv = new Dotenv();
                $dotenv->usePutenv();
                $dotenv->load($envFile);
            }

            $items = ConfigLoader::load(ROOT_PATH . '/config');
            Config::load($items);

            if (config('app.debug') === false) {
                ConfigLoader::writeCache($cacheFile, $items);
            }
        }

        date_default_timezone_set(config('app.timezone', 'UTC'));
        define('BASE_URL', config('app.url'));

    }

    private function registerCoreServices(): void
    {
        $this->container->singleton(MiddlewareKernel::class);
        $this->container->singleton(Router::class);
        $this->container->singleton(RouteDispatcher::class);
        $this->container->singleton(Kernel::class);
    }

    private function registerContainerBindings(): void
    {
        $config = (array) config('container', []);

        foreach ($config['singletons'] ?? [] as $class) {
            $this->container->singleton($class);
        }

        foreach ($config['bindings'] ?? [] as $abstract => $concrete) {
            $this->container->bind($abstract, $concrete);
        }
    }
}