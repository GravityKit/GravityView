<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Middleware;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\FinalCallback;
use JsonMapper\Tests\Implementation\ComplexObject;
use PHPUnit\Framework\TestCase;

class FinalCallbackTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\FinalCallback
     */
    public function testCallbackIsOnlyInvokedOnceOnNestedStructure(): void
    {
        $invocationCount = 0;
        $callback = static function () use (&$invocationCount) {
            $invocationCount++;
        };
        $mapper = (new JsonMapperFactory())->default();
        $mapper->push(new FinalCallback($callback));
        $object = new ComplexObject();
        $json = (object) ['user' => (object) ['name' => __METHOD__]];

        $mapper->mapObject($json, $object);

        self::assertEquals(1, $invocationCount);
    }

    /**
     * @covers \JsonMapper\Middleware\FinalCallback
     */
    public function testCallbackIsInvokedForEveryPass(): void
    {
        $invocationCount = 0;
        $callback = static function () use (&$invocationCount) {
            $invocationCount++;
        };
        $mapper = (new JsonMapperFactory())->default();
        $mapper->push(new FinalCallback($callback, false));
        $object = new ComplexObject();
        $json = (object) ['user' => (object) ['name' => __METHOD__]];

        $mapper->mapObject($json, $object);

        self::assertEquals(2, $invocationCount);
    }
}
