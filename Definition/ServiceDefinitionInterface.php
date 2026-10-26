<?php

namespace Centum\Core\Container\Definition;

interface ServiceDefinitionInterface {

    public function getConcrete(): string|object;

    public function addArgument(mixed $argument): self;

    public function addArguments(array $arguments): self;

    public function getArguments(): array;
}