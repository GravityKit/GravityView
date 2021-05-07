<?php

declare(strict_types=1);

namespace JsonMapper;

interface JsonMapperInterface
{
    public function setPropertyMapper(callable $handler): JsonMapperInterface;

    public function push(callable $middleware, string $name = ''): self;

    public function pop(): self;

    public function unshift(callable $middleware, string $name = ''): self;

    public function shift(): self;

    public function remove(callable $remove): self;

    public function removeByName(string $remove): self;

    public function mapObject(\stdClass $json, object $object): void;

    public function mapArray(array $json, object $object): array;

    public function mapObjectFromString(string $jsonString, object $object): void;

    public function mapArrayFromString(string $jsonStrings, object $object): array;
}
