<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\Visibility;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\NamespaceResolver;
use JsonMapper\Tests\Helpers\AssertThatPropertyTrait;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Models\User;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class NamespaceResolverTest extends TestCase
{
    use AssertThatPropertyTrait;

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItResolvesNamespacesForImportedNamespace(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new ComplexObject();
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType('User', false)
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('user'));
        $this->assertThatProperty($propertyMap->getProperty('user'))
            ->onlyHasType(User::class, false);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItResolvesNamespacesWithinSameNamespace(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new ComplexObject();
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType('SimpleObject', false)
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('child'));
        $this->assertThatProperty($propertyMap->getProperty('child'))
            ->onlyHasType(SimpleObject::class, false);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItDoesntApplyResolvingToScalarTypes(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new SimpleObject();
        $property = PropertyBuilder::new()
            ->setName('name')
            ->addType('string', false)
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('name'));
        $this->assertThatProperty($propertyMap->getProperty('name'))
            ->onlyHasType('string', false);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItDoesntApplyResolvingToFullyQualifiedClassName(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new SimpleObject();
        $property = PropertyBuilder::new()
            ->setName('name')
            ->addType(__CLASS__, false)
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('name'));
        $this->assertThatProperty($propertyMap->getProperty('name'))
            ->onlyHasType(__CLASS__, false);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItResolvesNamespacesForImportedNamespaceWithArray(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new ComplexObject();
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType('User', true)
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('user'));
        $this->assertThatProperty($propertyMap->getProperty('user'))
            ->onlyHasType(User::class, true);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testItResolvesNamespacesWithinSameNamespaceWithArray(): void
    {
        $middleware = new NamespaceResolver(new NullCache());
        $object = new ComplexObject();
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType('SimpleObject', true)
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('child'));
        $this->assertThatProperty($propertyMap->getProperty('child'))
            ->onlyHasType(SimpleObject::class, true);
    }

    /**
     * @covers \JsonMapper\Middleware\NamespaceResolver
     */
    public function testReturnsFromCacheWhenAvailable(): void
    {
        $propertyMap = new PropertyMap();
        $objectWrapper = $this->createMock(ObjectWrapper::class);
        $objectWrapper->method('getName')->willReturn(__METHOD__);
        $objectWrapper->expects(self::never())->method('getReflectedObject');
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('has')->with(Assert::stringContains(__METHOD__))->willReturn(true);
        $cache->method('get')->with(Assert::stringContains(__METHOD__))->willReturn($propertyMap);
        $middleware = new NamespaceResolver($cache);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), $objectWrapper, $propertyMap, $jsonMapper);
    }
}
