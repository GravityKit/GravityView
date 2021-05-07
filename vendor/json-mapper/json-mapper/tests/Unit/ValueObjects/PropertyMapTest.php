<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\ValueObjects;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Enums\Visibility;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\ValueObjects\Property;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\ValueObjects\PropertyType;
use PHPUnit\Framework\TestCase;

class PropertyMapTest extends TestCase
{
    /**
     * @covers \JsonMapper\ValueObjects\PropertyMap
     */
    public function testPropertyCanBeAdded(): void
    {
        $property = new Property(
            'name',
            Visibility::PUBLIC(),
            true,
            new PropertyType('string', false)
        );
        $map = new PropertyMap();
        $map->addProperty($property);

        self::assertTrue($map->hasProperty('name'));
        self::assertSame($property, $map->getProperty('name'));
    }

    /**
     * @covers \JsonMapper\ValueObjects\PropertyMap
     */
    public function testGetPropertyThrowsErrorWhenPropertyDoesntExist(): void
    {
        $map = new PropertyMap();

        $this->expectException(\Exception::class);
        $map->getProperty('missing');
    }

    /**
     * @covers \JsonMapper\ValueObjects\PropertyMap
     */
    public function testMapReturnsCorrectIterator(): void
    {
        $property = new Property(
            'name',
            Visibility::PUBLIC(),
            true,
            new PropertyType('string', false)
        );
        $map = new PropertyMap();
        $map->addProperty($property);
        $iterator = $map->getIterator();

        self::assertCount(1, $iterator);
        self::assertSame($property, $iterator->current());
    }

    /**
     * @covers \JsonMapper\ValueObjects\PropertyMap
     */
    public function testCanBeConvertedToJson(): void
    {
        $map = new PropertyMap();
        $map->addProperty(new Property('id', Visibility::PUBLIC(), false, new PropertyType('int', false)));

        $mapAsJson = json_encode($map);

        self::assertIsString($mapAsJson);
        self::assertJsonStringEqualsJsonString(
            '{"properties":{"id":{"name":"id","types":[{"type":"int","isArray":false}],"visibility":"public","isNullable":false}}}',
            (string) $mapAsJson
        );
    }

    /**
     * @covers \JsonMapper\ValueObjects\PropertyMap
     */
    public function testCanBeConvertedToString(): void
    {
        $map = new PropertyMap();
        $map->addProperty(new Property('id', Visibility::PUBLIC(), false, new PropertyType('int', false)));

        $mapAsString = $map->toString();

        self::assertIsString($mapAsString);
        self::assertJsonStringEqualsJsonString(
            '{"properties":{"id":{"name":"id","types":[{"type":"int","isArray":false}],"visibility":"public","isNullable":false}}}',
            (string) $mapAsString
        );
    }

    /**
     * @covers \JsonMapper\ValueObjects\PropertyMap
     */
    public function testCanBeMergedWithOtherPropertyMap(): void
    {
        $map = new PropertyMap();
        $map->addProperty(new Property('id', Visibility::PUBLIC(), false, new PropertyType('int', false)));
        $map->addProperty(new Property('data', Visibility::PUBLIC(), false, new PropertyType(Popo::class, true)));
        $other = new PropertyMap();
        $other->addProperty(new Property('uuid', Visibility::PUBLIC(), false, new PropertyType('string', false)));
        $other->addProperty(new Property('data', Visibility::PUBLIC(), false, new PropertyType('mixed', true)));

        $map->merge($other);

        self::assertTrue($map->hasProperty('id'));
        self::assertTrue($map->hasProperty('uuid'));
        self::assertTrue($map->hasProperty('data'));
        self::assertEquals(
            new Property(
                'data',
                Visibility::PUBLIC(),
                false,
                new PropertyType(Popo::class, true),
                new PropertyType('mixed', true)
            ),
            $map->getProperty('data')
        );
    }
}
