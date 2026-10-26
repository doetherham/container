<?php

namespace Centum\Core\Container\Definition;

class ServiceDefinition implements ServiceDefinitionInterface
{
    private array $arguments = [];

    public function __construct(
        private readonly string|object $concrete
    )
    {

    }

    public function getConcrete(): string|object
    {
        return $this->concrete;
    }

    public function addArgument(mixed $argument): self
    {
        $this->arguments[] = $argument;

        return $this;
    }

    public function addArguments(array $arguments): self
    {
        foreach ($arguments as $argument) {
            $this->arguments[] = $argument;
        }

        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

}