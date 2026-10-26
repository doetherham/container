<?php

namespace Centum\Core\Container\Resolver;

interface DependencyResolverInterface {
    public function resolve($class): mixed;
    public function resolve_class_dependencies(array $parameters): array;
    public function setContainer(\Psr\Container\ContainerInterface $container): void;
}