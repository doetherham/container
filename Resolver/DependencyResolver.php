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
            $service_type = $param->getType();

            if($service_type !== null && !$service_type->isBuiltin()) {
                $service = $this->container->get($service_type->getName());
                $dependencies[] = $service;
            } else {
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    throw new ContainerException("Cannot resolve scalar parameter '\${$param->getName()}'");
                }
            }
        }

        return $dependencies;
    }
}