<?php

declare(strict_types=1);

namespace JsonMapper\Handler;

use JsonMapper\Enums\ScalarType;
use JsonMapper\Enums\Visibility;
use JsonMapper\Exception\ClassFactoryException;
use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects\Property;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\ValueObjects\PropertyType;
use JsonMapper\Wrapper\ObjectWrapper;

class PropertyMapper
{
    /** @var FactoryRegistry */
    private $classFactoryRegistry;
    /**@var FactoryRegistry */
    private $nonInstantiableTypeResolver;

    public function __construct(
        FactoryRegistry $classFactoryRegistry = null,
        FactoryRegistry $nonInstantiableTypeResolver = null
    ) {
        if ($classFactoryRegistry === null) {
            $classFactoryRegistry = FactoryRegistry::WithNativePhpClassesAdded();
        }

        if ($nonInstantiableTypeResolver === null) {
            $nonInstantiableTypeResolver = new FactoryRegistry();
        }

        $this->classFactoryRegistry = $classFactoryRegistry;
        $this->nonInstantiableTypeResolver = $nonInstantiableTypeResolver;
    }

    public function __invoke(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        $values = (array) $json;
        foreach ($values as $key => $value) {
            if (! $propertyMap->hasProperty($key)) {
                continue;
            }

            $property = $propertyMap->getProperty($key);

            if (! $property->isNullable() && is_null($value)) {
                throw new \RuntimeException(
                    "Null provided in json where {$object->getName()}::{$key} doesn't allow null value"
                );
            }

            if ($property->isNullable() && is_null($value)) {
                $this->setValue($object, $property, null);
                continue;
            }

            $value = $this->mapPropertyValue($mapper, $property, $value);
            $this->setValue($object, $property, $value);
        }
    }

    /**
     * @param mixed $value
     */
    private function setValue(ObjectWrapper $object, Property $propertyInfo, $value): void
    {
        if ($propertyInfo->getVisibility()->equals(Visibility::PUBLIC())) {
            $object->getObject()->{$propertyInfo->getName()} = $value;
            return;
        }

        $methodName = 'set' . ucfirst($propertyInfo->getName());
        if (method_exists($object->getObject(), $methodName)) {
            $method = new \ReflectionMethod($object->getObject(), $methodName);
            $parameters = $method->getParameters();

            if (is_array($value) && count($parameters) === 1 && $parameters[0]->isVariadic()) {
                $object->getObject()->$methodName(...$value);
                return;
            }

            $object->getObject()->$methodName($value);
            return;
        }

        throw new \RuntimeException(
            "{$object->getName()}::{$propertyInfo->getName()} is non-public and no setter method was found"
        );
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function mapPropertyValue(JsonMapperInterface $mapper, Property $property, $value)
    {
        // For union types, loop through and see if value is a match with the type
        if (count($property->getPropertyTypes()) > 1) {
            foreach ($property->getPropertyTypes() as $type) {
                if (is_array($value) && $type->isArray()) {
                    $copy = (array) $value;
                    $firstValue = array_shift($copy);

                    /* Array of scalar values */
                    if ($this->propertyTypeAndValueTypeAreScalarAndSameType($type, $firstValue)) {
                        $scalarType = new ScalarType($type->getType());
                        return array_map(static function ($v) use ($scalarType) {
                            return $scalarType->cast($v);
                        }, (array) $value);
                    }

                    // Array of registered class @todo how do you know it was the correct type?
                    if ($this->classFactoryRegistry->hasFactory($type->getType())) {
                        return array_map(function ($v) use ($type) {
                            return $this->classFactoryRegistry->create($type->getType(), $v);
                        }, (array) $value);
                    }

                    // Array of existing class @todo how do you know it was the correct type?
                    if (class_exists($type->getType())) {
                        return array_map(
                            static function ($v) use ($type, $mapper) {
                                $className = $type->getType();
                                $instance = new $className();
                                $mapper->mapObject($v, $instance);
                                return $instance;
                            },
                            (array) $value
                        );
                    }

                    continue;
                }

                // Single scalar value
                if ($this->propertyTypeAndValueTypeAreScalarAndSameType($type, $value)) {
                    return (new ScalarType($type->getType()))->cast($value);
                }

                // Single registered class @todo how do you know it was the correct type?
                if ($this->classFactoryRegistry->hasFactory($type->getType())) {
                    return $this->classFactoryRegistry->create($type->getType(), $value);
                }

                // Single existing class @todo how do you know it was the correct type?
                if (class_exists($type->getType())) {
                    return $this->mapToObject($type->getType(), $value, false, $mapper);
                }
            }
        }

        // No match was found (or there was only one option) lets assume the first is the right one.
        $types = $property->getPropertyTypes();
        $type = array_shift($types);

        if ($type === null) {
            // Return the value as is as there is no type info.
            return $value;
        }

        if (ScalarType::isValid($type->getType())) {
            return $this->mapToScalarValue($type->getType(), $value, $type->isArray());
        }

        if ($this->classFactoryRegistry->hasFactory($type->getType())) {
            if ($type->isArray()) {
                return array_map(function ($v) use ($type) {
                    return $this->classFactoryRegistry->create($type->getType(), $v);
                }, $value);
            }
            return $this->classFactoryRegistry->create($type->getType(), $value);
        }

        return $this->mapToObject($type->getType(), $value, $type->isArray(), $mapper);
    }

    /**
     * @param mixed $value
     */
    private function propertyTypeAndValueTypeAreScalarAndSameType(PropertyType $type, $value): bool
    {
        if (! is_scalar($value) || ! ScalarType::isValid($type->getType())) {
            return false;
        }

        $valueType = gettype($value);
        if ($valueType === 'double') {
            $valueType = 'float';
        }

        return $type->getType() === $valueType;
    }

    /**
     * @param mixed $value
     * @return string|bool|int|float|string[]|bool[]|int[]|float[]
     */
    private function mapToScalarValue(string $type, $value, bool $asArray)
    {
        $scalar = new ScalarType($type);

        if ($asArray) {
            return array_map(function ($v) use ($scalar) {
                return $scalar->cast($v);
            }, (array) $value);
        }

        return $scalar->cast($value);
    }

    /**
     * @param mixed $value
     * @return object|object[]
     */
    private function mapToObject(string $type, $value, bool $asArray, JsonMapperInterface $mapper)
    {
        if ($asArray) {
            return array_map(
                function ($v) use ($type, $mapper): object {
                    return $this->mapToObject($type, $v, false, $mapper);
                },
                (array) $value
            );
        }

        $reflectionType = new \ReflectionClass($type);
        if (!$reflectionType->isInstantiable()) {
            return $this->resolveUnInstantiableType($type, $value, $mapper);
        }

        $instance = new $type();
        $mapper->mapObject($value, $instance);
        return $instance;
    }

    /**
     * @param mixed $value
     */
    private function resolveUnInstantiableType(string $type, $value, JsonMapperInterface $mapper): object
    {
        try {
            $instance = $this->nonInstantiableTypeResolver->create($type, $value);
            $mapper->mapObject($value, $instance);
            return $instance;
        } catch (ClassFactoryException $e) {
            throw new \RuntimeException("Unable to resolve un-instantiable {$type} as no factory was registered", 0, $e);
        }
    }
}
