<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware;

use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\Visibility;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Tests\Helpers\AssertThatPropertyTrait;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class DocBlockAnnotationsTest extends TestCase
{
    use AssertThatPropertyTrait;

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testUpdatesThePropertyMap(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new ComplexObject();
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('child'));
        self::assertThatProperty($propertyMap->getProperty('child'))
            ->hasType('SimpleObject', false)
            ->hasVisibility(Visibility::PRIVATE())
            ->isNullable();
        self::assertTrue($propertyMap->hasProperty('children'));
        self::assertThatProperty($propertyMap->getProperty('children'))
            ->hasType('SimpleObject', true)
            ->hasVisibility(Visibility::PRIVATE())
            ->isNotNullable();
        self::assertTrue($propertyMap->hasProperty('user'));
        self::assertThatProperty($propertyMap->getProperty('user'))
            ->hasType('User', false)
            ->hasVisibility(Visibility::PRIVATE())
            ->isNotNullable();
        self::assertTrue($propertyMap->hasProperty('mixedParam'));
        self::assertThatProperty($propertyMap->getProperty('mixedParam'))
            ->hasType('mixed', false)
            ->hasVisibility(Visibility::PUBLIC())
            ->isNotNullable();
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testItCanHandleMissingDocBlock(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new class {
            public $number;
        };

        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertEmpty($propertyMap->getIterator());
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testItCanHandleEmptyDocBlock(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new class {
            /** */
            public $number;
        };

        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertEmpty($propertyMap->getIterator());
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testItCanHandleIncompleteDocBlock(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new class {
            /** @var */
            public $number;
        };

        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertEmpty($propertyMap->getIterator());
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
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
        $middleware = new DocBlockAnnotations($cache);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), $objectWrapper, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testTypeIsCorrectlyCalculatedForNullableVars(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new class {
            /** @var NullableNumber|null This is a nullable number*/
            public $nullableNumber;
        };
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('nullableNumber'));
        self::assertThatProperty($propertyMap->getProperty('nullableNumber'))
            ->hasType('NullableNumber', false)
            ->hasVisibility(Visibility::PUBLIC())
            ->isNullable();
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testTypeIsCorrectlyCalculatedForNullableArray(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new class {
            /** @var Number[]|null This is a nullable array number*/
            public $numbers;
        };
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('numbers'));
        self::assertThatProperty($propertyMap->getProperty('numbers'))
            ->hasType('Number', true)
            ->hasVisibility(Visibility::PUBLIC())
            ->isNullable();
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testTypeIsCorrectlyCalculatedForNullableArrayWhenNullIsProvidedFirst(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new class {
            /** @var null|Number[] This is a nullable array number*/
            public $numbers;
        };
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('numbers'));
        self::assertThatProperty($propertyMap->getProperty('numbers'))
            ->hasType('Number', true)
            ->hasVisibility(Visibility::PUBLIC())
            ->isNullable();
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testTypedUnionPropertyIsCorrectlyDiscovered(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new class {
            /** @var float|int */
            public $amount;
        };
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('amount'));
        $this->assertThatProperty($propertyMap->getProperty('amount'))
            ->hasType('int', false)
            ->hasType('float', false)
            ->hasVisibility(Visibility::PUBLIC())
            ->isNotNullable();
    }

    /**
     * @covers \JsonMapper\Middleware\DocBlockAnnotations
     */
    public function testComplexUnionTypeIsCorrectlyDiscovered(): void
    {
        $middleware = new DocBlockAnnotations(new NullCache());
        $object = new class {
            /** @var string|int|float|array */
            public $complexUnionWithArray;
        };
        $propertyMap = new PropertyMap();
        $jsonMapper = $this->createMock(JsonMapperInterface::class);

        $middleware->handle(new \stdClass(), new ObjectWrapper($object), $propertyMap, $jsonMapper);

        self::assertTrue($propertyMap->hasProperty('complexUnionWithArray'));
        $this->assertThatProperty($propertyMap->getProperty('complexUnionWithArray'))
            ->onlyHasType('mixed', true)
            ->hasVisibility(Visibility::PUBLIC())
            ->isNotNullable();
    }
}
