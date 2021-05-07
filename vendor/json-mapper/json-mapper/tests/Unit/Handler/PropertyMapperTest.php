<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Handler;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Cache\NullCache;
use JsonMapper\Enums\Visibility;
use JsonMapper\Handler\ClassFactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperFactory;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\Models\User;
use JsonMapper\Tests\Implementation\Models\UserWithConstructor;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\Tests\Implementation\PrivatePropertyWithoutSetter;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\Tests\Implementation\UserWithConstructorParent;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class PropertyMapperTest extends TestCase
{
    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testAdditionalJsonIsIgnored(): void
    {
        $propertyMapper = new PropertyMapper();
        $json = (object) ['file' => __FILE__];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);

        $propertyMapper->__invoke($json, $wrapped, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertEquals(new \stdClass(), $object);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @dataProvider scalarValueDataTypes
     * @param mixed $value
     */
    public function testPublicScalarValueIsSet(string $type, $value): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType($type, false)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['value' => $value];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $this->createMock(JsonMapperInterface::class));

        self::assertEquals($value, $object->value);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicBuiltinClassIsSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('createdAt')
            ->addType(\DateTimeImmutable::class, false)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $now = new \DateTimeImmutable();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['createdAt' => $now->format('Y-m-d\TH:i:s.uP')];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $this->createMock(JsonMapperInterface::class));

        self::assertEquals($now, $object->createdAt);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicCustomClassIsSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType(SimpleObject::class, false)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $jsonMapper->expects(self::once())
            ->method('mapObject')
            ->with((object) ['name' => __FUNCTION__], self::isInstanceOf(SimpleObject::class))
            ->willReturnCallback(static function (\stdClass $json, SimpleObject $object) {
                $object->setName($json->name);
            });
        $json = (object) ['child' => (object) ['name' => __FUNCTION__]];
        $object = new ComplexObject();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        $child = $object->getChild();
        self::assertNotNull($child);
        self::assertEquals(__FUNCTION__, $child->getName());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicScalarValueArrayIsSet(): void
    {
        $fileProperty = PropertyBuilder::new()
            ->setName('ids')
            ->addType('int', true)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($fileProperty);
        $json = (object) ['ids' => [1, 2, 3]];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $this->createMock(JsonMapperInterface::class));

        self::assertEquals([1, 2, 3], $object->ids);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicCustomClassArrayIsSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('children')
            ->addType(SimpleObject::class, true)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $jsonMapper->expects(self::exactly(2))
            ->method('mapObject')
            ->with((object) ['name' => __FUNCTION__], self::isInstanceOf(SimpleObject::class))
            ->willReturnCallback(static function (\stdClass $json, SimpleObject $object) {
                $object->setName($json->name);
            });
        $json = (object) ['children' => [(object) ['name' => __FUNCTION__], (object) ['name' => __FUNCTION__]]];
        $object = new ComplexObject();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(2, count($object->getChildren()));
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testArrayPropertyIsCasted(): void
    {
        $property = PropertyBuilder::new()
            ->setName('notes')
            ->addType('string', true)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['notes' => (object) ['note_one' => __FUNCTION__, 'note_two' => __CLASS__]];
        $object = new Popo();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(['note_one' => __FUNCTION__, 'note_two' => __CLASS__], $object->notes);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testCanMapPropertyWithClassFactory(): void
    {
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType(UserWithConstructor::class, false)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['user' => (object) ['id' => 1234, 'name' => 'John Doe']];
        $object = new UserWithConstructorParent();
        $wrapped = new ObjectWrapper($object);
        $classFactoryRegistry = new ClassFactoryRegistry();
        $classFactoryRegistry->loadNativePhpClassFactories();
        $classFactoryRegistry->addFactory(
            UserWithConstructor::class,
            static function ($params) {
                return new UserWithConstructor($params->id, $params->name);
            }
        );
        $propertyMapper = new PropertyMapper($classFactoryRegistry);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(new UserWithConstructor(1234, 'John Doe'), $object->user);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testCanMapPropertyAsArrayWithClassFactory(): void
    {
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType(UserWithConstructor::class, true)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['user' => [
            0 => (object) ['id' => 1234, 'name' => 'John Doe'],
            1 => (object) ['id' => 5678, 'name' => 'Jane Doe']
        ]];
        $object = new UserWithConstructorParent();
        $wrapped = new ObjectWrapper($object);
        $classFactoryRegistry = new ClassFactoryRegistry();
        $classFactoryRegistry->loadNativePhpClassFactories();
        $classFactoryRegistry->addFactory(
            UserWithConstructor::class,
            static function ($params) {
                return new UserWithConstructor($params->id, $params->name);
            }
        );
        $propertyMapper = new PropertyMapper($classFactoryRegistry);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(
            [new UserWithConstructor(1234, 'John Doe'), new UserWithConstructor(5678, 'Jane Doe')],
            $object->user
        );
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testCanMapUnionPropertyAsArrayWithClassFactory(): void
    {
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType(UserWithConstructor::class, true)
            ->addType(\DateTime::class, true)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['user' => [
            0 => (object) ['id' => 1234, 'name' => 'John Doe'],
            1 => (object) ['id' => 5678, 'name' => 'Jane Doe']
        ]];
        $object = new UserWithConstructorParent();
        $wrapped = new ObjectWrapper($object);
        $classFactoryRegistry = new ClassFactoryRegistry();
        $classFactoryRegistry->loadNativePhpClassFactories();
        $classFactoryRegistry->addFactory(
            UserWithConstructor::class,
            static function ($params) {
                return new UserWithConstructor($params->id, $params->name);
            }
        );
        $propertyMapper = new PropertyMapper($classFactoryRegistry);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals(
            [new UserWithConstructor(1234, 'John Doe'), new UserWithConstructor(5678, 'Jane Doe')],
            $object->user
        );
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testWillSetNullOnNullablePropertyIfNullProvided(): void
    {
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType(SimpleObject::class, false)
            ->setIsNullable(true)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['child' => null];
        $object = new ComplexObject();
        $object->setChild(new SimpleObject());
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertNull($object->getChild());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testPublicNotNullableCustomClassThrowsException(): void
    {
        $property = PropertyBuilder::new()
            ->setName('child')
            ->addType(SimpleObject::class, false)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['child' => null];
        $object = new ComplexObject();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $this->expectException(\RuntimeException::class);
        $message = "Null provided in json where " . ComplexObject::class . "::child doesn't allow null value";
        $this->expectExceptionMessage($message);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testNonPublicPropertyWithoutSetterThrowsException(): void
    {
        $property = PropertyBuilder::new()
            ->setName('number')
            ->addType('int', false)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['number' => 42];
        $object = new PrivatePropertyWithoutSetter();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $this->expectException(\RuntimeException::class);
        $message = PrivatePropertyWithoutSetter::class . "::number is non-public and no setter method was found";
        $this->expectExceptionMessage($message);

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @dataProvider scalarValueDataTypes
     * @param mixed $value
     */
    public function testItCanMapAScalarUnionType(string $type, $value): void
    {
        $property = PropertyBuilder::new()
            ->setName('value')
            ->addType('int', false)
            ->addType('double', false)
            ->addType('float', false)
            ->addType('string', false)
            ->addType('bool', false)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['value' => $value];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($value, $object->value);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     * @dataProvider scalarValueDataTypes
     * @param mixed $value
     */
    public function testItCanMapAnArrayOfScalarUnionType(string $type, $value): void
    {
        $property = PropertyBuilder::new()
            ->setName('values')
            ->addType('int', true)
            ->addType('float', true)
            ->addType('string', true)
            ->addType('bool', true)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['values' => [(string) $value]];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals([$value], $object->values);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapAUnionOfUnixTimeStampAndDateTimeWithDateTimeObject(): void
    {
        $now = new \DateTime();
        $property = PropertyBuilder::new()
            ->setName('moment')
            ->addType('int', true)
            ->addType(\DateTime::class, true)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $jsonMapper = $this->createMock(JsonMapperInterface::class);
        $json = (object) ['moment' => $now->format('Y-m-d\TH:i:s.uP')];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($now, $object->moment);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapAUnionOfCustomClasses(): void
    {
        $property = PropertyBuilder::new()
            ->setName('user')
            ->addType(User::class, false)
            ->addType(Popo::class, false)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['user' => (object) ['id' => 42, 'name' => 'John Doe']];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($json->user->id, $object->user->getId());
        self::assertEquals($json->user->name, $object->user->getName());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapAUnionOfCustomClassesAsArray(): void
    {
        $property = PropertyBuilder::new()
            ->setName('users')
            ->addType(User::class, true)
            ->addType(Popo::class, true)
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['users' => [0 => (object) ['id' => 42, 'name' => 'John Doe']]];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($json->users[0]->id, $object->users[0]->getId());
        self::assertEquals($json->users[0]->name, $object->users[0]->getName());
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapIfNoTypeDetailIsAvailable(): void
    {
        $property = PropertyBuilder::new()
            ->setName('id')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PUBLIC())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['id' => 42];
        $object = new \stdClass();
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals($json->id, $object->id);
    }

    /**
     * @covers \JsonMapper\Handler\PropertyMapper
     */
    public function testItCanMapUsingAVariadicSetterFunction(): void
    {
        $property = PropertyBuilder::new()
            ->setName('numbers')
            ->setIsNullable(false)
            ->setVisibility(Visibility::PRIVATE())
            ->build();
        $propertyMap = new PropertyMap();
        $propertyMap->addProperty($property);
        $json = (object) ['numbers' => [1, 2, 3, 4, 5]];
        $object = new class {
            /** @var int[] */
            private $numbers;

            public function getNumbers(): array
            {
                return $this->numbers;
            }

            public function setNumbers(int ...$numbers): void
            {
                $this->numbers = $numbers;
            }
        };
        $wrapped = new ObjectWrapper($object);
        $propertyMapper = new PropertyMapper();
        $jsonMapper = (new JsonMapperFactory())->create($propertyMapper, new DocBlockAnnotations(new NullCache()));

        $propertyMapper->__invoke($json, $wrapped, $propertyMap, $jsonMapper);

        self::assertEquals([1, 2, 3, 4, 5], $object->getNumbers());
    }

    public function scalarValueDataTypes(): array
    {
        return [
            'string' => ['string', 'Some string'],
            'boolean' => ['bool', true],
            'integer' => ['int', 1],
            'float' => ['float', M_PI],
            'double' => ['double', M_PI],
        ];
    }
}
