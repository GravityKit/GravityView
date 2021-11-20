<?php

declare(strict_types=1);

namespace JsonMapper;

use JsonException;
use JsonMapper\Dto\NamedMiddleware;
use JsonMapper\Exception\TypeError;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;

class JsonMapper implements JsonMapperInterface
{
    /** @var callable */
    private $propertyMapper;
    /** @var NamedMiddleware[] */
    private $stack = [];
    /** @var callable|null */
    private $cached;

    public function __construct(callable $propertyMapper = null)
    {
        $this->propertyMapper = $propertyMapper;
    }

    public function setPropertyMapper(callable $propertyMapper): JsonMapperInterface
    {
        $this->propertyMapper = $propertyMapper;
        $this->cached = null;

        return $this;
    }

    public function push(callable $middleware, string $name = ''): JsonMapperInterface
    {
        $this->stack[] = new NamedMiddleware($middleware, $name);
        $this->cached = null;

        return $this;
    }

    public function pop(): JsonMapperInterface
    {
        \array_pop($this->stack);
        $this->cached = null;

        return $this;
    }

    public function unshift(callable $middleware, string $name = ''): JsonMapperInterface
    {
        \array_unshift($this->stack, new NamedMiddleware($middleware, $name));
        $this->cached = null;

        return $this;
    }

    public function shift(): JsonMapperInterface
    {
        \array_shift($this->stack);
        $this->cached = null;

        return $this;
    }

    public function remove(callable $remove): JsonMapperInterface
    {
        $this->stack = \array_values(\array_filter(
            $this->stack,
            static function (NamedMiddleware $namedMiddleware) use ($remove) {
                return $namedMiddleware->getMiddleware() !== $remove;
            }
        ));
        $this->cached = null;

        return $this;
    }

    public function removeByName(string $remove): JsonMapperInterface
    {
        $this->stack = \array_values(\array_filter(
            $this->stack,
            static function (NamedMiddleware $namedMiddleware) use ($remove) {
                return $namedMiddleware->getName() !== $remove;
            }
        ));
        $this->cached = null;

        return $this;
    }

    /** @param object $object */
    public function mapObject(\stdClass $json, $object): void
    {
        if (! \is_object($object)) {
            throw TypeError::forObjectArgument(__METHOD__, $object, 2);
        }

        $propertyMap = new PropertyMap();

        $handler = $this->resolve();
        $handler($json, new ObjectWrapper($object), $propertyMap, $this);
    }

    /** @param object $object */
    public function mapArray(array $json, $object): array
    {
        if (! \is_object($object)) {
            throw TypeError::forObjectArgument(__METHOD__, $object, 2);
        }

        $results = [];
        foreach ($json as $key => $value) {
            $results[$key] = clone $object;
            $this->mapObject($value, $results[$key]);
        }

        return $results;
    }

    /** @param object $object */
    public function mapObjectFromString(string $json, $object): void
    {
        if (! \is_object($object)) {
            throw TypeError::forObjectArgument(__METHOD__, $object, 2);
        }

        $data = $this->decodeJsonString($json);

        if (! $data instanceof \stdClass) {
            throw new \RuntimeException('Provided string is not a json encoded object');
        }

        $this->mapObject($data, $object);
    }

    /** @param object $object */
    public function mapArrayFromString(string $json, $object): array
    {
        if (! \is_object($object)) {
            throw TypeError::forObjectArgument(__METHOD__, $object, 2);
        }

        $data = $this->decodeJsonString($json);

        if (! \is_array($data)) {
            throw new \RuntimeException('Provided string is not a json encoded array');
        }

        $results = [];
        foreach ($data as $key => $value) {
            $results[$key] = clone $object;
            $this->mapObject($value, $results[$key]);
        }

        return $results;
    }

    /** @return \stdClass|\stdClass[] */
    private function decodeJsonString(string $json)
    {
        if (PHP_VERSION_ID >= 70300) {
            $data = \json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } else {
            $data = \json_decode($json, false);
            if (\json_last_error() !== JSON_ERROR_NONE) {
                throw new \JsonException(json_last_error_msg(), \json_last_error());
            }
        }

        return $data;
    }

    private function resolve(): callable
    {
        if (!$this->cached) {
            $prev = $this->propertyMapper;
            foreach (\array_reverse($this->stack) as $namedMiddleware) {
                $prev = $namedMiddleware->getMiddleware()($prev);
            }

            $this->cached = $prev;
        }

        return $this->cached;
    }
}
