<?php

declare(strict_types=1);

namespace JsonMapper\Middleware;

use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\ScalarType;
use JsonMapper\Helpers\UseStatementHelper;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Parser\Import;
use JsonMapper\ValueObjects\Property;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\ValueObjects\PropertyType;
use JsonMapper\Wrapper\ObjectWrapper;
use Psr\SimpleCache\CacheInterface;

class NamespaceResolver extends AbstractMiddleware
{
    /** @var CacheInterface */
    private $cache;

    public function __construct(CacheInterface $cache = null)
    {
        $this->cache = $cache ?? new NullCache();
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        foreach ($this->fetchPropertyMapForObject($object, $propertyMap) as $property) {
            $propertyMap->addProperty($property);
        }
    }

    private function fetchPropertyMapForObject(ObjectWrapper $object, PropertyMap $originalPropertyMap): PropertyMap
    {
        $cacheKey = \sprintf('%s::Cache::%s', __CLASS__, $object->getName());
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $intermediatePropertyMap = new PropertyMap();
        $imports = UseStatementHelper::getImports($object->getReflectedObject());

        /** @var Property $property */
        foreach ($originalPropertyMap as $property) {
            $types = $property->getPropertyTypes();
            foreach ($types as $index => $type) {
                $types[$index] = $this->resolveSingleType($type, $object, $imports);
            }
            $intermediatePropertyMap->addProperty($property->asBuilder()->setTypes(...$types)->build());
        }

        $this->cache->set($cacheKey, $intermediatePropertyMap);

        return $intermediatePropertyMap;
    }

    /** @param Import[] $imports */
    private function resolveSingleType(PropertyType $type, ObjectWrapper $object, array $imports): PropertyType
    {
        if (ScalarType::isValid($type->getType())) {
            return $type;
        }

        $matches = \array_filter(
            $imports,
            static function (Import $import) use ($type) {
                $nameSpacedType = "\\{$type->getType()}";
                if ($import->hasAlias() && $import->getAlias() === $type->getType()) {
                    return true;
                }

                return $nameSpacedType === \substr($import->getImport(), -strlen($nameSpacedType));
            }
        );

        if (count($matches) > 0) {
            return new PropertyType(\array_shift($matches)->getImport(), $type->isArray());
        }

        $reflectedObject = $object->getReflectedObject();
        while (true) {
            if (class_exists($reflectedObject->getNamespaceName() . '\\' . $type->getType())) {
                return new PropertyType(
                    $reflectedObject->getNamespaceName() . '\\' . $type->getType(),
                    $type->isArray()
                );
            }

            $reflectedObject = $reflectedObject->getParentClass();
            if (! $reflectedObject) {
                break;
            }
        }

        return $type;
    }
}
