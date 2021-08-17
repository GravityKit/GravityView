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

    /** @param object $object */
    public function mapObject(\stdClass $json, $object): void;

    /** @param object $object */
    public function mapArray(array $json, $object): array;

    /** @param object $object */
    public function mapObjectFromString(string $jsonString, $object): void;

    /** @param object $object */
    public function mapArrayFromString(string $jsonStrings, $object): array;
}
