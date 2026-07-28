<?php

namespace Bhitti\Core;

use Closure;
use ReflectionClass;
use Exception;

class Container
{
    protected array $bindings = [];
    protected array $singletons = [];
    protected static array $reflectionCache = [];

    /**
     * Bind a class or key
     */
    public function bind(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    /**
     * Bind as singleton (high performance)
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->singletons[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'resolved' => false,
            'instance' => null,
        ];
    }

    /**
     * Resolve any class
     */
    public function make(string $abstract)
    {
        // Singleton fast return
        if (isset($this->singletons[$abstract])) {
            if ($this->singletons[$abstract]['resolved']) {
                return $this->singletons[$abstract]['instance'];
            }

            $object = $this->build($this->singletons[$abstract]['concrete']);
            $this->singletons[$abstract]['instance'] = $object;
            $this->singletons[$abstract]['resolved'] = true;

            return $object;
        }

        // Normal binding
        $concrete = $this->bindings[$abstract] ?? $abstract;
        return $this->build($concrete);
    }

    /**
     * Build object with automatic DI (Reflection Cached)
     */
    protected function build(Closure|string $concrete)
    {
        // Closure support
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        // Reflection cache (BIG PERFORMANCE BOOST)
        if (!isset(self::$reflectionCache[$concrete])) {
            self::$reflectionCache[$concrete] = new ReflectionClass($concrete);
        }

        $reflector = self::$reflectionCache[$concrete];

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (!$constructor) {
            return new $concrete;
        }

        $params = $constructor->getParameters();
        $dependencies = [];

        foreach ($params as $param) {
            $type = $param->getType();

            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new Exception("Unresolvable dependency [{$param->getName()}]");
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Check binding exists
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->singletons[$abstract]);
    }
}
