<?php

namespace Centum\Core\Container;

use Centum\Core\Container\Exceptions\ContainerException;
use Psr\Container\ContainerInterface;

use Centum\Core\Container\Resolver\DependencyResolver;
use Centum\Core\Container\Definition\ServiceDefinition;

class Container implements ContainerInterface
{
    private array $services = [];
    private array $delegates = [];
    private array $instances = [];
    private array $shared = [];
    private DependencyResolver $resolver;

    public function __construct(
        ?DependencyResolver $resolver = null,
    )
    {
        $this->resolver = $resolver ?? new DependencyResolver();
        $this->resolver->setContainer($this);
    }

    public function add(string $id, string|object|null $concrete = null): void
    {
        if (is_null($concrete)) {
            if (!class_exists($id)) {
                throw new ContainerException("Service '$id' does not exist.");
            }

            $concrete = $id;
        }

        $this->services[$id] = $concrete;
    }

    public function addShared(string $id, string|object|null $concrete = null): void
    {
        $this->add($id, $concrete);
        $this->shared[$id] = true;
    }

    public function get(string $id): mixed
    {
        if(array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            if (!class_exists($id)) {
                throw new ContainerException("Service '$id' could not be resolved.");
            }

            $this->add($id);
        }

        $instance = $this->resolver->resolve($this->services[$id]);

        if (isset($this->shared[$id])) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->services)) {
            return true;
        }

        return array_any($this->delegates, fn($delegate) => $delegate->has($id));
    }

    /**
     * @param ContainerInterface $container
     * @return $this
     */
    public function delegate(ContainerInterface $container): self
    {
        $this->delegates[] = $container;

        return $this;
    }
}