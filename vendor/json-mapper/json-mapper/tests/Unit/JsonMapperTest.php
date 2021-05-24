<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit;

use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapper;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\AbstractMiddleware;
use JsonMapper\Tests\Implementation\IsCalledHandler;
use JsonMapper\Tests\Implementation\IsCalledMiddleware;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class JsonMapperTest extends TestCase
{
    /** @var IsCalledHandler */
    private $handler;
    /** @var IsCalledMiddleware */
    private $middleware;

    protected function setUp(): void
    {
        $this->handler = new IsCalledHandler();
        $this->middleware = new IsCalledMiddleware();
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testHandlerFromConstructorIsInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testHandlerFromSetterIsInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper();
        $jsonMapper->setPropertyMapper($this->handler);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testHandlerFromConstructorIsInvokedWhenMappingArray(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapArray([new \stdClass()], new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testHandlerFromSetterIsInvokedWhenMappingArray(): void
    {
        $jsonMapper = new JsonMapper();
        $jsonMapper->setPropertyMapper($this->handler);

        $jsonMapper->mapArray([new \stdClass()], new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testPushedMiddlewareIsInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertTrue($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testPushedMiddlewareIsInvokedWhenMappingArray(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertTrue($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testRemovedMiddlewareIsNotInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware);
        $jsonMapper->remove($this->middleware);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertFalse($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testRemovedByNameMiddlewareIsNotInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware, __METHOD__);
        $jsonMapper->removeByName(__METHOD__);

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertFalse($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testShiftedMiddlewareIsNotInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->unshift($this->middleware, __METHOD__);
        $jsonMapper->shift();

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertFalse($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testPoppedMiddlewareIsNotInvokedWhenMappingObject(): void
    {
        $jsonMapper = new JsonMapper(new PropertyMapper());
        $jsonMapper->push($this->middleware, __METHOD__);
        $jsonMapper->pop();

        $jsonMapper->mapObject(new \stdClass(), new \stdClass());

        self::assertFalse($this->middleware->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapObjectFromStringWithInvalidJsonThrowsException(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\JsonException::class);
        $jsonMapper->mapObjectFromString('abcdef...', new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapObjectFromStringWithJsonArrayThrowsException(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Provided string is not a json encoded object');
        $jsonMapper->mapObjectFromString('[1,2,3]', new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapObjectFromStringWithJsonObjectCallsHandler(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapObjectFromString('{}', new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapArrayFromStringWithInvalidJsonThrowsException(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\JsonException::class);
        $jsonMapper->mapArrayFromString('abcdef...', new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapArrayFromStringWithJsonObjectThrowsException(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Provided string is not a json encoded array');
        $jsonMapper->mapArrayFromString('{"one": 1}', new \stdClass());
    }

    /**
     * @covers \JsonMapper\JsonMapper
     */
    public function testMapArrayFromStringWithJsonArrayCallsHandler(): void
    {
        $jsonMapper = new JsonMapper($this->handler);

        $jsonMapper->mapArrayFromString('[{"one": 1}]', new \stdClass());

        self::assertTrue($this->handler->isCalled());
    }
}
