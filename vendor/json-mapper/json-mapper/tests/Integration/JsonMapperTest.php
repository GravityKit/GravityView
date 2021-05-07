<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Rename\Rename;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Models\User;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\Tests\Implementation\Php74;
use JsonMapper\Tests\Implementation\SimpleObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JsonMapper\JsonMapper
 */
class JsonMapperTest extends TestCase
{
    public function testItCanMapAnObjectUsingAPublicProperty(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = (object) ['name' => __METHOD__];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->name);
    }

    public function testItAppliesTypeCastingWhenMappingAnObjectUsingAPublicProperty(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = (object) ['name' => 42];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame('42', $object->name);
    }

    public function testItCanMapAnObjectUsingAPublicSetter(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new SimpleObject();
        $json = (object) ['name' => __METHOD__];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->getName());
    }

    public function testItAppliesTypeCastingWhenMappingAnObjectUsingAPublicSetter(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new SimpleObject();
        $json = (object) ['name' => 42];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame('42', $object->getName());
    }

    public function testItCanMapAnDateTimeImmutableProperty(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = (object) ['date' => '2020-03-08 12:42:14'];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals(new \DateTimeImmutable('2020-03-08 12:42:14'), $object->date);
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testItCanMapAnObjectWithTypedProperties(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php74\Popo();
        $json = (object) ['name' => __METHOD__];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->name);
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testItAppliesTypeCastingMappingAnObjectWithTypedProperties(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php74\Popo();
        $json = (object) ['name' => 42];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame('42', $object->name);
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testItHandlesPropertyTypedAsArray(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php74\Popo();
        $json = (object) ['friends' => [__METHOD__, __CLASS__]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame([__METHOD__, __CLASS__], $object->friends);
    }

    public function testItHandlesPropertyDocumentedAsArrayProvidedAsObject(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = (object) ['notes' => (object) ['one' => __METHOD__, 'two' => __CLASS__]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(['one' => __METHOD__, 'two' => __CLASS__], $object->notes);
    }

    public function testItCanMapAnObjectWithACustomClassAttribute(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $json = (object) ['child' => (object) ['name' => __METHOD__]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        $child = $object->getChild();
        self::assertNotNull($child);
        self::assertSame(__METHOD__, $child->getName());
    }

    public function testItCanMapAnObjectWithANullClassAttribute(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $json = (object) ['child' => null];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertNull($object->getChild());
    }

    public function testItCanMapAnObjectWithACustomClassAttributeFromAnotherNamespace(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $json = (object) ['user' => (object) ['name' => __METHOD__]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame(__METHOD__, $object->getUser()->getName());
    }

    public function testItCanMapAnObjectWithAnArrayOfScalarValues(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $one = new SimpleObject();
        $one->setName('ONE');
        $two = new SimpleObject();
        $two->setName('TWO');
        $json = (object) ['children' => [(object) ['name' => 'ONE'], (object) ['name' => 'TWO']]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertIsArray($object->getChildren());
        self::assertContainsOnly(SimpleObject::class, $object->getChildren());
        self::assertEquals([$one, $two], $object->getChildren());
    }

    public function testItCanMapAnObjectFromString(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json =  '{"name": "one"}';

        // Act
        $mapper->mapObjectFromString($json, $object);

        // Assert
        self::assertSame('one', $object->name);
    }

    public function testItWillThrowAnExceptionWhenMappingObjectFromStringWithJsonArray(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = '[{"name": "one"}, {"name": "two"}]';
        $this->expectException(\RuntimeException::class);

        // Act
        $mapper->mapObjectFromString($json, $object);
    }

    public function testItWillThrowExceptionOnInvalidJson(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $jsonString =  '{"name": one}';
        $this->expectException(\JsonException::class);

        // Act
        $mapper->mapObjectFromString($jsonString, $object);
    }

    public function testItCanMapAnArrayOfObjects(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new SimpleObject();
        $json = [(object) ['name' => 'one'], (object) ['name' => 'two']];

        // Act
        $result = $mapper->mapArray($json, $object);

        // Assert
        self::assertContainsOnly(SimpleObject::class, $result);
        self::assertSame('one', $result[0]->getName());
        self::assertSame('two', $result[1]->getName());
    }

    public function testItCanMapArrayFromString(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new SimpleObject();
        $json = '[{"name": "one"}, {"name": "two"}]';

        // Act
        $result = $mapper->mapArrayFromString($json, $object);

        // Assert
        self::assertContainsOnly(SimpleObject::class, $result);
        self::assertSame('one', $result[0]->getName());
        self::assertSame('two', $result[1]->getName());
    }

    public function testItWillThrowAnExceptionWhenMappingArrayFromStringWithJsonObject(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Popo();
        $json = '{"name": "one"}';
        $this->expectException(\RuntimeException::class);

        // Act
        $mapper->mapArrayFromString($json, $object);
    }

    /**
     * @dataProvider scalarValueDataTypes
     * @param mixed $value
     */
    public function testItSetsTheValueAsIsForMixedType($value): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new ComplexObject();
        $json = (object) ['mixedParam' => $value];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertSame($value, $object->mixedParam);
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testItMapsClassFromTheSameNamespace(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new Php74\PopoWrapper();
        $json = (object) ['wrappee' => (object) ['name' => 'two']];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertNotNull($object->wrappee);
        self::assertSame('two', $object->wrappee->name);
    }

    public function testItCanMapANullableArrayOfScalarValues(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var int[]|null */
            public $numbers;
        };
        $json = (object) ['numbers' => null];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertNull($object->numbers);
    }

    public function testItCanMapANullableArrayOfObjects(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var \DateTime[]|null */
            public $dates;
        };
        $json = (object) ['dates' => null];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertNull($object->dates);
    }

    /**
     * @dataProvider scalarValueDataTypes
     * @param int|float|string|bool $value
     */
    public function testItCanMapAScalarUnionType($value): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var int|float|string|bool */
            public $value;
        };
        $json = (object) ['value' => (string) $value];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals($value, $object->value);
    }

    /**
     * @dataProvider scalarValueDataTypes
     * @param int|float|string|bool $value
     */
    public function testItCanMapAnArrayOfScalarUnionType($value): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var int[]|float[]|string[]|bool[] */
            public $values;
        };
        $json = (object) ['values' => [(string) $value]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals([$value], $object->values);
    }

    public function testItCanMapAUnionOfUnixTimeStampAndDateTimeWithDateTimeObject(): void
    {
        // Arrange
        $now = new \DateTime();
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /**
             * Either a unix timestamp (int) or a date time object
             * @var int|\DateTime
             */
            public $moment;
        };
        $json = (object) ['moment' => $now->format('Y-m-d\TH:i:s.uP')];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals($now, $object->moment);
    }

    public function testItCanMapAnArrayUsingAVariadicSetter(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $object = new class {
            /** @var Popo[] */
            private $popos;

            public function setPopos(Popo ...$popos): void
            {
                $this->popos = $popos;
            }

            public function getPopos(): array
            {
                return $this->popos;
            }
        };
        $json = (object) ['popos' => [(object) ['name' => 'one'], (object) ['name' => 'two']]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals($object->getPopos()[0]->name, $json->popos[0]->name);
        self::assertEquals($object->getPopos()[1]->name, $json->popos[1]->name);
    }

    public function testItCanRenameJsonProperties(): void
    {
        // Arrange
        $rename = new Rename();
        $rename->addMapping(User::class, 'Full-Name', 'name');
        $rename->addMapping(User::class, 'Identifier', 'id');
        $mapper = (new JsonMapperFactory())->bestFit();
        $mapper->unshift($rename);
        $object = new User();
        $json = (object) ['Full-Name' => 'John Doe', 'Identifier' => '42'];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertEquals('John Doe', $object->getName());
        self::assertEquals(42, $object->getId());
    }

    public function testItCanRenameJsonPropertiesOnNestedObjects(): void
    {
        // Arrange
        $rename = new Rename();
        $rename->addMapping(SimpleObject::class, 'FULL-NAME', 'name');
        $rename->addMapping(ComplexObject::class, 'sub', 'children');
        $mapper = (new JsonMapperFactory())->bestFit();
        $mapper->unshift($rename);
        $object = new ComplexObject();
        $json = (object) ['sub' => [(object) ['FULL-NAME' => 'John Doe'], (object) ['FULL-NAME' => 'Jane Doe']]];

        // Act
        $mapper->mapObject($json, $object);

        // Assert
        self::assertCount(2, $object->getChildren());
        self::assertEquals([new SimpleObject('John Doe'), new SimpleObject('Jane Doe')], $object->getChildren());
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testItCanMapArrayOfObjectWithTypeHintAndDocBlock(): void
    {
        // Arrange
        $mapper = (new JsonMapperFactory())->bestFit();
        $response = new Php74\Response();
        $json = (object) ['data' => [(object) ['name' => 'John Doe'], (object) ['name' => 'Jane Doe']]];

        // Act
        $mapper->mapObject($json, $response);

        // Assert
        self::assertCount(2, $response->data);
        self::assertContainsOnlyInstancesOf(Php74\Popo::class, $response->data);
        $john = new Php74\Popo();
        $john->name = 'John Doe';
        $jane = new Php74\Popo();
        $jane->name = 'Jane Doe';
        self::assertEquals([$john, $jane], $response->data);
    }

    public function scalarValueDataTypes(): array
    {
        return [
            'string' => ['Some string'],
            'boolean' => [true],
            'integer' => [1],
            'float' => [M_PI],
        ];
    }
}
