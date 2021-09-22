<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware;

use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\Debugger;
use JsonMapper\Middleware\FinalCallback;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class DebuggerTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Debugger
     */
    public function testLoggerIsInvoked(): void
    {
        $logger = new TestLogger();
        $middleware = new Debugger($logger);
        $object = new ObjectWrapper(new SimpleObject());
        $function = $middleware->__invoke(new PropertyMapper());

        $function(new \stdClass(), $object, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertCount(1, $logger->records);
        self::assertTrue($logger->hasDebug('Current state attributes passed through JsonMapper middleware'));
    }
}
