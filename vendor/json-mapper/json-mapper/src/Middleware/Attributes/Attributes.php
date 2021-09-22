<?php

declare(strict_types=1);

namespace JsonMapper\Middleware\Attributes;

use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\AbstractMiddleware;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use ReflectionClass;

class Attributes extends AbstractMiddleware
{
    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        $reflectionClass = new ReflectionClass($object->getObject());

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(MapFrom::class);

            foreach ($attributes as $attribute) {
                /** @var MapFrom $mapFrom */
                $mapFrom = $attribute->newInstance();
                $source = $mapFrom->source;
                $target = $property->name;

                if ($source === $target) {
                    continue;
                }

                if (isset($json->$source)) {
                    $json->$target = $json->$source;
                    unset($json->$source);
                }
            }
        }
    }
}
