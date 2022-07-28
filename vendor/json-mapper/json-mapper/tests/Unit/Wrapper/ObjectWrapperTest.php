<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Wrapper;

use JsonMapper\JsonMapper;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class ObjectWrapperTest extends TestCase
{
    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testConstructorInvalidObjectThrowsTypeException(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf(
            '%s::__construct(): Argument #1 ($object) must be of type object, string given, called in %s on line %d',
            ObjectWrapper::class,
            __FILE__,
            __LINE__ + 2
        ));
        new ObjectWrapper('');
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testWrapsOriginalObject(): void
    {
        $object = new \stdClass();
        $wrapper = new ObjectWrapper($object);

        self::assertEquals($object, $wrapper->getObject());
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testReflectedObjectIsOfWrappedObject(): void
    {
        $object = new \stdClass();
        $wrapper = new ObjectWrapper($object);
        $reflectedObject = $wrapper->getReflectedObject();

        self::assertEquals(get_class($object), $reflectedObject->getName());
    }

    /**
     * @covers \JsonMapper\Wrapper\ObjectWrapper
     */
    public function testCanGetNameOfWrappedObject(): void
    {
        $object = new \stdClass();
        $wrapper = new ObjectWrapper($object);

        self::assertEquals(\stdClass::class, $wrapper->getName());
    }
}
