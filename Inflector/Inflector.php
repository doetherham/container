<?php

namespace Centum\Core\Container\Inflector;

use Psr\Container\ContainerInterface;
class Inflector
{
    private array $methods = [];
    private array $properties = [];

    public function __construct(private readonly string $type) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function invokeMethod(string $name, array $args = []): static
    {
        $this->methods[$name] = $args;
        return $this;
    }

    public function setProperty(string $name, mixed $value): static
    {
        $this->properties[$name] = $value;
        return $this;
    }

    public function inflect(object $object, ContainerInterface $container): void
    {
        foreach ($this->methods as $method => $args) {
            $object->$method(...$this->resolveArgs($args, $container));
        }

        foreach ($this->properties as $property => $value) {
            $object->$property = $this->resolveArgs([$value], $container)[0];
        }
    }

    private function resolveArgs(array $args, ContainerInterface $container): array
    {
        return array_map(
            fn($arg) => is_string($arg) && $container->has($arg) ? $container->get($arg) : $arg,
            $args
        );
    }
}