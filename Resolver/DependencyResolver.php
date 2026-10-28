<?php

namespace Centum\Core\Container\Resolver;

use Centum\Core\Container\Exceptions\ContainerException;
use Psr\Container\ContainerInterface;

class DependencyResolver implements DependencyResolverInterface
{

    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function resolve($class): mixed
    {
        $reflection_class = new \ReflectionClass($class);

        $constructor = $reflection_class->getConstructor();

        if(is_null($constructor)) {
            return $reflection_class->newInstance();
        }

        $parameters = $reflection_class->getConstructor()->getParameters();

        $class_dependencies = $this->resolve_class_dependencies($parameters);

        $instance = $reflection_class->newInstanceArgs($class_dependencies);

        return $instance;
    }
    public function resolve_class_dependencies(array $parameters): array
    {
        $dependencies = [];

        /** @var \ReflectionParameter $param */
        foreach ($parameters as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->container->get($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } elseif ($type?->allowsNull()) {
                $dependencies[] = null;
            } else {
                throw new ContainerException("Cannot resolve parameter '\${$param->getName()}'");
            }
        }

        return $dependencies;
    }
}