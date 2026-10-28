<?php

namespace Centum\Core\Container;

use Centum\Core\Container\Exceptions\ContainerException;
use Centum\Core\Container\Exceptions\NotFoundException;

use Psr\Container\ContainerInterface;

use Centum\Core\Container\Resolver\DependencyResolver;
use Centum\Core\Container\Definition\ServiceDefinition;
use Centum\Core\Container\Inflector\Inflector;

class Container implements ContainerInterface
{
    /** @var ServiceDefinition[] */
    private array $definitions = [];
    /** @var Inflector[] */
    private array $inflectors = [];
    private array $delegates = [];
    private array $instances = [];

    public function __construct(private ?DependencyResolver $resolver = null)
    {
        $this->resolver ??= new DependencyResolver();
        $this->resolver->setContainer($this);
    }

    public function add(string $id, string|object|null $concrete = null, bool $shared = false): ServiceDefinition
    {
        return $this->definitions[$id] = (new ServiceDefinition($concrete ?? $id))->setShared($shared);
    }

    public function addShared(string $id, string|object|null $concrete = null): ServiceDefinition
    {
        return $this->add($id, $concrete, true);
    }

    public function inflector(string $type): Inflector
    {
        return $this->inflectors[$type] ??= new Inflector($type);
    }

    public function delegate(ContainerInterface $container): static
    {
        $this->delegates[] = $container;
        return $this;
    }

    public function has(string $id): bool
    {
        if (isset($this->definitions[$id])) {
            return true;
        }
        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                return true;
            }
        }
        return false;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];
            $instance = $this->build($definition);

            if (is_object($instance)) {
                $this->inflect($instance);
            }
            if ($definition->isShared()) {
                $this->instances[$id] = $instance;
            }
            return $instance;
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                return $delegate->get($id);
            }
        }

        throw new NotFoundException("Service '$id' not found.");
    }

    private function build(ServiceDefinition $definition): mixed
    {
        $concrete = $definition->getConcrete();

        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }
        if (is_object($concrete)) {
            return $concrete;
        }

        if ($definition->getArguments()) {
            $args = array_map(
                fn($a) => is_string($a) && $this->has($a) ? $this->get($a) : $a,
                $definition->getArguments()
            );
            return new $concrete(...$args);
        }

        return $this->resolver->resolve($concrete); // авто-вайринг
    }

    private function inflect(object $object): void
    {
        foreach ($this->inflectors as $type => $inflector) {
            if ($object instanceof $type) {
                $inflector->inflect($object, $this);
            }
        }
    }
}