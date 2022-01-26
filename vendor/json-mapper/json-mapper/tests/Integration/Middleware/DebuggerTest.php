<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Middleware;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Debugger;
use JsonMapper\Tests\Implementation\ComplexObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class DebuggerTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Debugger
     */
    public function testDebuggerLogsDetails(): void
    {
        $mapper = (new JsonMapperFactory())->default();
        $logger = new TestLogger();
        $mapper->push(new Debugger($logger));
        $json = (object) ['User' => (object) ['Name' => __METHOD__]];
        $object = new ComplexObject();

        $mapper->mapObject($json, $object);

        self::assertCount(1, $logger->records);
        self::assertTrue($logger->hasDebug('Current state attributes passed through JsonMapper middleware'));
    }
}
