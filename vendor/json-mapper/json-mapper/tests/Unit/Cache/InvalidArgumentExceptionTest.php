<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Cache;

use JsonMapper\Cache\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvalidArgumentExceptionTest extends TestCase
{
    /**
     * @covers \JsonMapper\Cache\InvalidArgumentException
     */
    public function testCanBeConstructedFromNamedConstructor(): void
    {
        $e = InvalidArgumentException::forCacheKey(__FUNCTION__);

        self::assertEquals('An invalid cache key was provided.', $e->getMessage());
        self::assertEquals(__FUNCTION__, $e->getInvalidArgument());
    }
}
