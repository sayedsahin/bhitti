<?php

declare(strict_types=1);

namespace Bhitti\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

class Container
{
    protected array $bindings = [];
    protected array $singletons = [];
    protected static array $reflectionCache = [];

    public function bind(string $abstract, Closure|string|null $concrete = null): void
    {
        unset($this->singletons[$abstract]);
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        unset($this->bindings[$abstract]);
        $this->singletons[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'resolved' => false,
            'instance' => null,
        ];
    }

    public function instance(string $abstract, object $instance): void
    {
        unset($this->bindings[$abstract]);

        $this->singletons[$abstract] = [
            'concrete' => $abstract,
            'resolved' => true,
            'instance' => $instance,
        ];
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->singletons[$abstract])) {
            if ($this->singletons[$abstract]['resolved']) {
                return $this->singletons[$abstract]['instance'];
            }

            $object = $this->build($this->singletons[$abstract]['concrete']);
            $this->singletons[$abstract]['instance'] = $object;
            $this->singletons[$abstract]['resolved'] = true;

            return $object;
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;
        return $this->build($concrete);
    }

    /**
     * Build a fresh instance while supplying scalar/array constructor values.
     * Class-typed dependencies are still resolved through the container.
     */
    public function makeWith(string $abstract, array $parameters = []): mixed
    {
        if ($parameters === []) {
            return $this->make($abstract);
        }

        $concrete = $this->bindings[$abstract]
            ?? $this->singletons[$abstract]['concrete']
            ?? $abstract;

        return $this->build($concrete, $parameters);
    }

    protected function build(Closure|string $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, ...$parameters);
        }

        if (!isset(self::$reflectionCache[$concrete])) {
            self::$reflectionCache[$concrete] = new ReflectionClass($concrete);
        }

        $reflector = self::$reflectionCache[$concrete];

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException(
                "Class [{$concrete}] is not instantiable."
            );
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = [];
        $position = 0;

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $dependencies[] = $parameters[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            if (array_key_exists($position, $parameters)) {
                $dependencies[] = $parameters[$position];
                $position++;
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $dependencies[] = null;
                continue;
            }

            throw new RuntimeException(
                "Unresolvable dependency [{$name}] in class [{$concrete}]."
            );
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->singletons[$abstract]);
    }
}
