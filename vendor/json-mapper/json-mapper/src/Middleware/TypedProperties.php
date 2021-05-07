<?php

declare(strict_types=1);

namespace JsonMapper\Middleware;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Enums\Visibility;
use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use Psr\SimpleCache\CacheInterface;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

class TypedProperties extends AbstractMiddleware
{
    /** @var CacheInterface */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        $propertyMap->merge($this->fetchPropertyMapForObject($object));
    }

    private function fetchPropertyMapForObject(ObjectWrapper $object): PropertyMap
    {
        if ($this->cache->has($object->getName())) {
            return $this->cache->get($object->getName());
        }

        $reflectionProperties = $object->getReflectedObject()->getProperties();
        $intermediatePropertyMap = new PropertyMap();

        foreach ($reflectionProperties as $reflectionProperty) {
            $type = $reflectionProperty->getType();

            if ($type instanceof ReflectionNamedType) {
                $isArray = $type->getName() === 'array';
                $propertyType = $isArray ? 'mixed' : $type->getName();
                $property = PropertyBuilder::new()
                    ->setName($reflectionProperty->getName())
                    ->addType($propertyType, $isArray)
                    ->setIsNullable($type->allowsNull() || ((!$isArray) && $propertyType === 'mixed'))
                    ->setVisibility(Visibility::fromReflectionProperty($reflectionProperty))
                    ->build();
                $intermediatePropertyMap->addProperty($property);

                continue;
            }

            if ($type instanceof ReflectionUnionType) {
                $types = array_map(static function (ReflectionNamedType $t): string {
                    return $t->getName();
                }, $type->getTypes());
                $isArray = in_array('array', $types, true);

                $builder = PropertyBuilder::new()
                    ->setName($reflectionProperty->getName())
                    ->setVisibility(Visibility::fromReflectionProperty($reflectionProperty))
                    ->setIsNullable($type->allowsNull());

                /* A union type that has one of its types defined as array is to complex to understand */
                if ($isArray) {
                    $property = $builder->addType('mixed', true)->build();
                    $intermediatePropertyMap->addProperty($property);
                    continue;
                }

                foreach ($types as $type) {
                    $builder->addType($type, false);
                }
                $property = $builder->build();
                $intermediatePropertyMap->addProperty($property);
            }
        }

        $this->cache->set($object->getName(), $intermediatePropertyMap);

        return $intermediatePropertyMap;
    }
}
